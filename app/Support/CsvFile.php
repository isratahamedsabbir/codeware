<?php

namespace App\Support;

use Generator;
use RuntimeException;

/**
 * Reads an uploaded CSV into header-keyed rows.
 *
 * Admin "Export CSV" buttons write a header row with human-readable column
 * names ("Name (EN)", "Sort Order", ...). Importers must accept that same file
 * back, so header keys are normalised to a fixed, lowercase shape instead of
 * relying on exact casing or spacing:
 *
 *   "Name (EN)"   -> "name en"
 *   "Sort Order"  -> "sort order"
 *   "Published At" -> "published at"
 *
 * The UTF-8 BOM a spreadsheet round-trip leaves on the first cell is stripped
 * too, otherwise the first header would silently never match.
 */
class CsvFile
{
    /**
     * Yields [line number => header-keyed row] for every non-blank data row.
     *
     * The first non-blank line is treated as the header; it is not yielded.
     * Rows keep their line number so an importer's error messages can point an
     * admin at the offending spreadsheet line.
     *
     * @return Generator<int, array<string, string>>
     */
    public static function rows(string $path): Generator
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('The CSV file could not be read.');
        }

        try {
            $headers = null;
            $line = 0;

            while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;

                if (static::isBlank($cells)) {
                    continue;
                }

                if ($headers === null) {
                    $headers = array_map(
                        fn ($header) => static::key((string) $header),
                        $cells,
                    );

                    if (array_filter($headers, fn (string $key) => $key !== '') === []) {
                        throw new RuntimeException('The CSV file has no header row.');
                    }

                    continue;
                }

                $row = [];

                foreach ($headers as $index => $key) {
                    if ($key === '') {
                        continue;
                    }

                    $row[$key] = trim((string) ($cells[$index] ?? ''));
                }

                yield $line => $row;
            }

            if ($headers === null) {
                throw new RuntimeException('The CSV file is empty.');
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Normalises one header cell into its canonical lookup key.
     */
    public static function key(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        // Everything that is not a letter or digit becomes a single space, so
        // "(EN)" / "EN" / "[EN]" all end up as the same "en" suffix.
        $header = preg_replace('/[^a-z0-9]+/u', ' ', mb_strtolower($header)) ?? '';

        return trim($header);
    }

    /**
     * @param  array<int, string|null>  $cells
     */
    protected static function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
