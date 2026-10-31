<?php

namespace App\Services\Export;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared plumbing for the admin "Export CSV" buttons.
 *
 * Each concrete exporter only describes which records match the screen's
 * filters and how one record maps onto a spreadsheet row. This base class
 * writes the UTF-8 BOM (so Excel does not mangle Bengali text), the header row,
 * and the streamed download itself.
 */
abstract class CsvExporter
{
    /**
     * Streams the matching records as a CSV download.
     *
     * With `$ids` (the index screen's bulk-selection "Export" button) exactly
     * that selection is exported, ignoring search/status. Otherwise the
     * filters currently active on the screen are honored. `$template` writes
     * only the header row, so the matching importer can offer a blank sheet
     * with the exact columns it expects.
     *
     * @param  list<int>  $ids
     */
    public function export(array $ids, string $search, string $status, bool $template = false): StreamedResponse
    {
        $records = $template ? collect() : $this->records($ids, $search, $status);

        $filename = ($template ? $this->templateBaseName() : $this->fileBaseName())
            .'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $this->headers());

            foreach ($records as $record) {
                fputcsv($handle, $this->row($record));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * The records an untemplated export should write.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Model>
     */
    abstract protected function records(array $ids, string $search, string $status): Collection;

    /**
     * The spreadsheet's header row, in the order the importer expects.
     *
     * @return list<string>
     */
    abstract protected function headers(): array;

    /**
     * One record's cells, in the same order as {@see headers()}.
     *
     * @return list<int|float|string|null>
     */
    abstract protected function row(Model $record): array;

    /**
     * The download name before the timestamp suffix, e.g. "posts".
     */
    abstract protected function fileBaseName(): string;

    /**
     * The download name used for the header-only import template.
     */
    protected function templateBaseName(): string
    {
        return $this->fileBaseName().'-import-template';
    }
}
