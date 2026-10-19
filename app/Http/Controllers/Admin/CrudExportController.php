<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crud;
use App\Support\Cruds;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrudExportController extends Controller
{
    /**
     * Streams a generated CRUD's records as CSV — the given `ids` when the bulk
     * selection is used, otherwise every record.
     */
    public function export(Request $request, string $slug): StreamedResponse
    {
        $crud = Crud::where('slug', $slug)->firstOrFail();
        $ids = array_map('intval', (array) $request->query('ids', []));

        $query = $crud->recordModel()->newQuery()->orderBy('id');

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        return response()->streamDownload(function () use ($crud, $query) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_merge(['ID'], array_column($crud->fields, 'label'), ['Created At']));

            $query->lazyById()->each(function ($record) use ($handle, $crud) {
                $row = [$record->id];

                foreach ($crud->fields as $f) {
                    $value = $record->getRawOriginal($f['name']);
                    $row[] = Cruds::csvCell($f['type'] === 'boolean' ? ($value ? 'Yes' : 'No') : $value);
                }

                $row[] = $record->created_at?->toDateTimeString();
                fputcsv($handle, $row);
            });

            fclose($handle);
        }, $crud->slug.'-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
