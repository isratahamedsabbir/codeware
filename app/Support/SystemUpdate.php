<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Applies an update package (a .zip of the project) over the running install.
 * Files in the zip overwrite the ones on disk; nothing is ever deleted, and
 * everything that is the site's own data (.env, storage, theme settings) is left
 * alone. Each file that gets replaced is first copied to storage/app/update-backups.
 */
class SystemUpdate
{
    /** Top-level folders/files an update never touches. */
    private const PROTECTED = ['.env', '.git', 'node_modules', 'storage', 'bootstrap/cache', 'public/storage', 'public/hot'];

    /**
     * @return array{files: int, backup: string}
     *
     * @throws \RuntimeException
     */
    public static function apply(string $zipPath): array
    {
        $zip = new \ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('This file is not a valid zip package.');
        }

        $temp = storage_path('app/update-tmp/'.uniqid('u', true));
        File::ensureDirectoryExists($temp);

        try {
            self::extract($zip, $temp);
            $zip->close();

            $source = self::locateRoot($temp);
            $backup = storage_path('app/update-backups/'.date('Ymd-His'));
            $count = self::overlay($source, $source, $backup);

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
            Plugins::flush();

            return ['files' => $count, 'backup' => $backup];
        } finally {
            @$zip->close();
            File::deleteDirectory($temp);
        }
    }

    /** Extracts entry by entry, refusing any path that would land outside $dest. */
    private static function extract(\ZipArchive $zip, string $dest): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));

            if ($name === '' || str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name) || preg_match('#^[A-Za-z]:#', $name)) {
                throw new \RuntimeException('The package contains an unsafe path and was rejected.');
            }

            if (str_starts_with($name, '__MACOSX/')) {
                continue;
            }

            $target = $dest.'/'.$name;

            if (str_ends_with($name, '/')) {
                File::ensureDirectoryExists($target);

                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            file_put_contents($target, $zip->getFromIndex($i));
        }
    }

    /** The project root is either the zip root or its single top-level folder. */
    private static function locateRoot(string $temp): string
    {
        $entries = array_values(array_filter(
            scandir($temp) ?: [],
            fn (string $e) => ! in_array($e, ['.', '..', '__MACOSX'], true)
        ));

        $root = (count($entries) === 1 && is_dir($temp.'/'.$entries[0])) ? $temp.'/'.$entries[0] : $temp;

        if (! is_file($root.'/artisan') || ! is_file($root.'/composer.json')) {
            throw new \RuntimeException('This does not look like a Codeware update package (artisan and composer.json are missing).');
        }

        return $root;
    }

    private static function overlay(string $dir, string $root, string $backup): int
    {
        $count = 0;

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $from = $dir.'/'.$entry;
            $rel = ltrim(substr($from, strlen($root)), '/');

            if (self::isProtected($rel)) {
                continue;
            }

            $to = base_path($rel);

            if (is_dir($from)) {
                File::ensureDirectoryExists($to);
                $count += self::overlay($from, $root, $backup);

                continue;
            }

            if (is_file($to)) {
                File::ensureDirectoryExists(dirname($backup.'/'.$rel));
                copy($to, $backup.'/'.$rel);
            }

            copy($from, $to);
            $count++;
        }

        return $count;
    }

    private static function isProtected(string $rel): bool
    {
        foreach (self::PROTECTED as $path) {
            if ($rel === $path || str_starts_with($rel, $path.'/')) {
                return true;
            }
        }

        // A theme's settings live in its theme.json — keep the site's copy.
        return (bool) preg_match('#^themes/[^/]+/theme\.json$#', $rel) && is_file(base_path($rel));
    }
}
