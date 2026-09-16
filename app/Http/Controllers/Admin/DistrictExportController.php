<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DistrictExportController extends Controller
{
    /**
     * Streams the given district ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Districts screen (see
     * Livewire\Admin\Districts\Index::$selectedIds), so this always exports
     * an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $districts = District::query()
            ->with(['division', 'country'])
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        $filename = 'districts-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($districts) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'Division', 'Country', 'Status', 'Created At']);

            foreach ($districts as $district) {
                fputcsv($handle, [
                    $district->id,
                    $district->name,
                    $district->division?->name,
                    $district->country?->name,
                    $district->status,
                    $district->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
