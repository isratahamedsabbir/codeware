<?php

namespace App\Services\Import;

use App\Support\CsvFile;
use App\Support\Slug;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Shared plumbing for the admin "Import CSV" buttons.
 *
 * Each concrete importer only describes how one spreadsheet row maps onto its
 * model; this base class opens the upload, keeps a tally, and turns any row
 * that throws into a readable "Line N: ..." entry instead of aborting the
 * whole import. A CSV column is optional unless its importer says otherwise,
 * so a file exported by this panel can always be imported back unchanged.
 */
abstract class CsvImporter
{
    /** How many row-level failures/warnings to keep for the result modal. */
    public const MAX_MESSAGES = 25;

    /**
     * Imports every row of the CSV at $path.
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     errors: list<string>,
     *     warnings: list<string>
     * }
     */
    public function import(string $path, bool $updateExisting, int $userId): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        $checkedHeader = false;

        foreach (CsvFile::rows($path) as $line => $row) {
            if (! $checkedHeader) {
                if (! array_key_exists($this->requiredColumn(), $row)) {
                    throw new RuntimeException(
                        'The CSV is missing the "'.$this->requiredColumnLabel().'" column. Download a template from this screen and use its header row.'
                    );
                }

                $checkedHeader = true;
            }

            try {
                $outcome = $this->importRow($row, $updateExisting, $userId);
            } catch (Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = "Line {$line}: ".$e->getMessage();

                continue;
            }

            $summary[$outcome['status']]++;

            foreach ($outcome['warnings'] ?? [] as $warning) {
                $summary['warnings'][] = "Line {$line}: {$warning}";
            }
        }

        $summary['errors'] = array_slice($summary['errors'], 0, static::MAX_MESSAGES);
        $summary['warnings'] = array_slice($summary['warnings'], 0, static::MAX_MESSAGES);

        return $summary;
    }

    /**
     * The normalised header key a file must have before any row is touched.
     */
    abstract protected function requiredColumn(): string;

    /**
     * The human-readable name of that column, for the error message.
     */
    abstract protected function requiredColumnLabel(): string;

    /**
     * Creates or updates one row.
     *
     * @param  array<string, string>  $row
     * @return array{status: 'created'|'updated'|'skipped', warnings?: list<string>}
     */
    abstract protected function importRow(array $row, bool $updateExisting, int $userId): array;

    /**
     * @param  array<string, string>  $row
     */
    protected function has(array $row, string $key): bool
    {
        return array_key_exists($key, $row);
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $keys
     */
    protected function hasAny(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($row, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function value(array $row, string $key): string
    {
        return trim($row[$key] ?? '');
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function empty(array $row, string $key): bool
    {
        return $this->value($row, $key) === '';
    }

    /**
     * Builds a spatie/laravel-translatable payload from the export's paired
     * "X (EN)" / "X (BN)" columns, dropping blank locales.
     *
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    protected function translations(array $row, string $enKey, string $bnKey): array
    {
        return array_filter([
            'en' => $this->value($row, $enKey),
            'bn' => $this->value($row, $bnKey),
        ], fn (string $value) => $value !== '');
    }

    /**
     * Accepts the "Yes"/"No" values the export writes, plus the usual
     * spreadsheet spellings.
     *
     * @param  array<string, string>  $row
     */
    protected function boolean(array $row, string $key, bool $default = false): bool
    {
        if (($value = strtolower($this->value($row, $key))) === '') {
            return $default;
        }

        return in_array($value, ['1', 'yes', 'y', 'true', 'on'], true);
    }

    /**
     * Normalises the active/inactive status column. Returns null when the
     * column is blank so an update can leave the existing status alone, while
     * a create falls back to inactive (the same state the admin forms use).
     *
     * @param  array<string, string>  $row
     */
    protected function status(array $row, bool $creating): ?string
    {
        $value = strtolower($this->value($row, 'status'));

        if (in_array($value, ['active', 'published', 'publish', 'live'], true)) {
            return 'active';
        }

        if (in_array($value, ['inactive', 'draft', 'unpublished', 'hidden'], true)) {
            return 'inactive';
        }

        return $creating ? 'inactive' : null;
    }

    /**
     * Parses a date cell, returning null for a blank one.
     *
     * @param  array<string, string>  $row
     */
    protected function date(array $row, string $key): ?Carbon
    {
        $value = $this->value($row, $key);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            throw new RuntimeException('"'.$key.'" is not a valid date: '.$value);
        }
    }

    /**
     * Keeps the requested slug, or falls back to the title, and appends -2,
     * -3... until it no longer collides. Slugs are globally unique on `pages`,
     * so two imported rows with the same title would otherwise fail the whole
     * row on the unique index.
     */
    protected function uniqueSlug(string $desired, ?int $ignorePageId = null, string $fallback = 'item'): string
    {
        $desired = trim($desired);
        $base = $desired === '' ? $fallback : Slug::lower($desired);

        if (Slug::make($base) === '') {
            // A title with no Latin characters (e.g. Bengali-only) slugifies
            // to nothing; keep the fallback rather than writing an empty slug.
            $base = $fallback;
        }

        $slug = $base;
        $suffix = 2;

        while (DB::table('pages')
            ->where('slug', $slug)
            ->when($ignorePageId !== null, fn ($query) => $query->where('id', '!=', $ignorePageId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
