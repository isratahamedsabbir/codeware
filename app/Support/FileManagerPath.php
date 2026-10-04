<?php

namespace App\Support;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves File Manager paths against the project root, guaranteeing every
 * resolved path stays inside it. Used by both the FileManager Livewire
 * component and FileManagerController so the traversal guard lives in one
 * place.
 *
 * Staying inside the project root is necessary but not sufficient, so this
 * also owns what the File Manager may touch at all. Rooting it at
 * `base_path()` means the tree it walks contains `.env`, the compiled
 * `bootstrap/cache/*.php`, and every PHP file the app runs — and the component
 * can create, rename, upload and edit files, not just read them. With only the
 * traversal guard in place, `view-file-manager` was enough to download the
 * database password, and `manage-file-manager` was enough to drop a `.php` file
 * into `public/` and have the server execute it.
 *
 * The rule that closes both: **no server-executable or secret file is
 * reachable, and none can be created.** That keeps the tool doing what it is for
 * (assets, media, text, theme and plugin files) instead of narrowing it to a
 * single directory and breaking every path its tests and UI already assume.
 */
class FileManagerPath
{
    /**
     * Directories that are never listed, opened, or written into. Matched as
     * project-root-relative prefixes, so `vendor/laravel/framework` is denied
     * by the `vendor` entry and `storage/framework/views/x` by its own.
     *
     * - `vendor` / `node_modules`: huge, and useless to browse by hand.
     * - `bootstrap`: holds the compiled route/package caches and a `cache/`
     *   directory of generated PHP; both a secret-adjacent read and a write that
     *   can be executed.
     * - `storage/framework`: session payloads, compiled views, cache.
     * - `storage/logs`: exception traces quote environment values.
     * - `public/build`: Vite output — generated, and overwritten by the next
     *   `npm run build` anyway.
     */
    private const DENIED_DIRECTORIES = [
        '.git', '.github', '.idea', '.vscode',
        'vendor', 'node_modules',
        'bootstrap',
        'storage/framework', 'storage/logs',
        'public/build',
    ];

    /**
     * Individual files that are never reachable, matched case-insensitively.
     *
     * `.env` and its siblings are handled separately by DENIED_NAME_PREFIXES,
     * because the dangerous ones are whichever variant happens to be checked in.
     */
    private const DENIED_FILENAMES = [
        'artisan', 'server.php', 'web.config',
        '.htaccess', '.htpasswd', '.npmrc', '.dockerignore',
        'id_rsa', 'id_dsa', 'id_ecdsa', 'id_ed25519',
    ];

    /**
     * Filename prefixes that are never reachable. `.env` is the one that
     * matters: it holds the app key, the database password and every API
     * credential in the deployment, and it sits at the root of the tree this
     * class is rooted at.
     */
    private const DENIED_NAME_PREFIXES = ['.env'];

    /**
     * Extensions the File Manager will neither read nor write, at any depth.
     *
     * Anything PHP-FPM or a CGI handler will execute if it lands under a served
     * directory, plus the config formats that get sourced rather than executed
     * (`.ini`, `.user.ini`). Writing one of these into `public/` or a symlinked
     * storage disk is remote code execution, and the File Manager can write
     * files into both.
     */
    private const DENIED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phtml', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'ksh', 'ps1', 'bat', 'cmd', 'com',
        'exe', 'dll', 'so', 'jsp', 'jspx', 'asp', 'aspx', 'ashx', 'cfm',
        'ini', 'user', 'htaccess', 'htpasswd',
    ];

    /**
     * Extensions that must never be *written*, even though reading them is
     * harmless. Private keys and certificates are write-once material: the
     * File Manager has no business minting one, and a `.pem` in `storage/` is a
     * credential the app never intended to hold.
     */
    private const DENIED_WRITE_EXTENSIONS = ['key', 'pem', 'crt', 'cer', 'p12', 'pfx', 'jks', 'keystore'];

    public static function root(): string
    {
        return realpath(base_path());
    }

    /**
     * Whether a project-root-relative path is off limits to the File Manager.
     *
     * Answers "no" for the root itself and for every ordinary asset path, so
     * this is safe to consult on anything the UI browses.
     */
    public static function isDenied(string $relative): bool
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        if ($relative === '') {
            return false;
        }

        $segments = explode('/', $relative);
        $basename = strtolower((string) end($segments));

        foreach (self::DENIED_NAME_PREFIXES as $prefix) {
            if (str_starts_with($basename, $prefix)) {
                return true;
            }
        }

        if (in_array($basename, self::DENIED_FILENAMES, true)) {
            return true;
        }

        // Check every ancestor, not just the leaf, so `vendor/x` and
        // `vendor/anything/deeper` are both denied.
        $ancestor = '';

        foreach ($segments as $segment) {
            $ancestor = $ancestor === '' ? strtolower($segment) : $ancestor.'/'.strtolower($segment);

            if (in_array($ancestor, self::DENIED_DIRECTORIES, true)) {
                return true;
            }
        }

        return self::extensionIsDenied($basename, self::DENIED_EXTENSIONS);
    }

    /**
     * Whether a single path segment may be used to create or rename a file.
     *
     * Separate from isDenied() because a *candidate* name has no ancestor to
     * check — only its own extension and spelling matter — and because the
     * caller has already resolved the directory it will be written into.
     */
    public static function nameIsAllowed(string $name): bool
    {
        if ($name === '' || in_array($name, ['.', '..'], true)) {
            return false;
        }

        // A separator in the name would smuggle a path past the directory the
        // caller resolved.
        if (str_contains($name, '/') || str_contains($name, '\\')) {
            return false;
        }

        $lower = strtolower($name);

        foreach (self::DENIED_NAME_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return false;
            }
        }

        if (in_array($lower, self::DENIED_FILENAMES, true)) {
            return false;
        }

        return ! self::extensionIsDenied($lower, self::DENIED_EXTENSIONS)
            && ! self::extensionIsDenied($lower, self::DENIED_WRITE_EXTENSIONS);
    }

    /**
     * Resolve a project-root-relative path (e.g. "app/Models") to an
     * absolute, real path. Throws if the path escapes the project root
     * (blocks `../` traversal), lands on something the File Manager must not
     * touch, or doesn't exist.
     */
    public static function resolve(string $relative): string
    {
        $root = self::root();
        $relative = trim(str_replace('\\', '/', $relative), '/');

        if ($relative === '') {
            return $root;
        }

        if (self::isDenied($relative)) {
            throw new NotFoundHttpException('Invalid path.');
        }

        // Reject traversal lexically first — cheap, and doesn't depend on
        // the filesystem being reachable the way realpath() does below.
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new NotFoundHttpException('Invalid path.');
            }
        }

        $candidate = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($candidate);

        if ($real !== false) {
            if ($real === $root || str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
                return $real;
            }

            throw new NotFoundHttpException('Invalid path.');
        }

        // realpath() can fail on Windows past the legacy MAX_PATH limit
        // (~260 chars) even for a directory that genuinely exists and is
        // otherwise safe — deep vendor/node_modules nesting hits this
        // often. The traversal check above already guarantees $candidate
        // stays under $root, so falling back to it (rather than 404ing a
        // real folder) is safe.
        if (is_dir($candidate) || is_file($candidate)) {
            return $candidate;
        }

        throw new NotFoundHttpException('Invalid path.');
    }

    /**
     * Turn an absolute path back into a project-root-relative one, using
     * forward slashes regardless of OS.
     */
    public static function relative(string $absolute): string
    {
        $root = self::root();

        return ltrim(str_replace('\\', '/', substr($absolute, strlen($root))), '/');
    }

    /**
     * Extension match, with the two spots that need care: a name with no
     * extension (a folder, or `Makefile`) is not denied by this, and a
     * dotfile like `.htaccess` is matched on its whole name as well as its
     * (empty) extension, because pathinfo() reports no extension for it.
     */
    private static function extensionIsDenied(string $lowerName, array $denied): bool
    {
        if (in_array($lowerName, $denied, true)) {
            return true;
        }

        $ext = strtolower(pathinfo($lowerName, PATHINFO_EXTENSION));

        return $ext !== '' && in_array($ext, $denied, true);
    }
}
