<?php

namespace App\Support;

use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Reads and writes the project's .env file directly. This is a genuinely sensitive
 * operation — a bad write can take the whole app down (wrong DB credentials, etc.) — so
 * every write is preceded by a timestamped backup under storage/app/env-backups/, and only
 * keys whose value actually changed are rewritten; every other line (comments, blank lines,
 * untouched keys, and keys passed in unchanged) is preserved byte-for-byte, including their
 * original quoting style and the file's line-ending convention.
 */
class EnvFile
{
    /**
     * Overridable in tests so they never touch the real project .env file.
     */
    public static ?string $pathOverride = null;

    public static function path(): string
    {
        return static::$pathOverride ?? base_path('.env');
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $values = [];

        foreach (static::lines() as $line) {
            if (preg_match('/^([A-Z_][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
                $values[$matches[1]] = static::unquote($matches[2]);
            }
        }

        return $values;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::all()[$key] ?? $default;
    }

    /**
     * Update the given keys. A key is only rewritten (and its line's original quoting style
     * lost) when its value actually changed; passing back a value identical to what's already
     * on disk leaves that line completely untouched. Any key not already present as a line in
     * the file is appended at the end.
     *
     * @param  array<string, string>  $values
     *
     * @throws RuntimeException if the backup or the write itself fails — a save that can't be
     *                          backed up or written must not be reported as successful.
     */
    public static function set(array $values): void
    {
        $raw = static::raw();
        $eol = str_contains($raw, "\r\n") ? "\r\n" : "\n";

        static::backup($raw);

        $current = static::all();
        $lines = preg_split('/\r\n|\r|\n/', rtrim($raw));
        $remaining = array_filter($values, fn ($value, $key) => ($current[$key] ?? null) !== $value, ARRAY_FILTER_USE_BOTH);

        foreach ($lines as $i => $line) {
            if (preg_match('/^([A-Z_][A-Z0-9_]*)=/', $line, $matches) && array_key_exists($matches[1], $remaining)) {
                $lines[$i] = $matches[1].'='.static::quote($remaining[$matches[1]]);
                unset($remaining[$matches[1]]);
            }
        }

        foreach ($remaining as $key => $value) {
            $lines[] = $key.'='.static::quote($value);
        }

        $written = @file_put_contents(static::path(), implode($eol, $lines).$eol);

        if ($written === false) {
            throw new RuntimeException('Could not write to '.static::path().' — check file permissions.');
        }
    }

    protected static function raw(): string
    {
        $contents = @file_get_contents(static::path());

        if ($contents === false) {
            throw new RuntimeException('Could not read '.static::path().'.');
        }

        return $contents;
    }

    /**
     * @return array<int, string>
     */
    protected static function lines(): array
    {
        return preg_split('/\r\n|\r|\n/', rtrim(static::raw()));
    }

    protected static function quote(string $value): string
    {
        if ($value === '' || preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }

    protected static function unquote(string $value): string
    {
        $value = trim($value);

        if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
            return stripcslashes(substr($value, 1, -1));
        }

        if (strlen($value) >= 2 && $value[0] === "'" && str_ends_with($value, "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    protected static function backup(string $raw): void
    {
        $dir = storage_path('app/env-backups');

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Could not create backup directory {$dir}.");
        }

        $written = @file_put_contents($dir.'/env-'.now()->format('Ymd-His-u').'.env', $raw);

        if ($written === false) {
            throw new RuntimeException("Could not write env backup to {$dir}.");
        }

        // Keep only the most recent 20 backups.
        Collection::make(glob($dir.'/env-*.env'))
            ->sortDesc()
            ->skip(20)
            ->each(fn (string $file) => @unlink($file));
    }
}
