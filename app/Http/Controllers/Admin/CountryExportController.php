<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CountryExportController extends Controller
{
    /**
     * Streams the given country ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Countries screen (see
     * Livewire\Admin\Countries\Index::$selectedIds), so this always exports
     * an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $countries = Country::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        $filename = 'countries-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($countries) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'Status', 'Created At']);

            foreach ($countries as $country) {
                fputcsv($handle, [
                    $country->id,
                    $country->name,
                    $country->status,
                    $country->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
