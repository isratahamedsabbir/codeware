<?php

namespace App\Support;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves File Manager paths against the project root, guaranteeing every
 * resolved path stays inside it. Used by both the FileManager Livewire
 * component and FileManagerController so the traversal guard lives in one
 * place.
 */
class FileManagerPath
{
    public static function root(): string
    {
        return realpath(base_path());
    }

    /**
     * Resolve a project-root-relative path (e.g. "app/Models") to an
     * absolute, real path. Throws if the path escapes the project root
     * (blocks `../` traversal) or doesn't exist.
     */
    public static function resolve(string $relative): string
    {
        $root = self::root();
        $relative = trim(str_replace('\\', '/', $relative), '/');

        if ($relative === '') {
            return $root;
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
}
