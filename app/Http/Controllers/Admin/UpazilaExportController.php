<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upazila;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UpazilaExportController extends Controller
{
    /**
     * Streams the given upazila ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Upazilas screen (see
     * Livewire\Admin\Upazilas\Index::$selectedIds), so this always exports
     * an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $upazilas = Upazila::query()
            ->with('district.division')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        $filename = 'upazilas-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($upazilas) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'District', 'Division', 'Status', 'Created At']);

            foreach ($upazilas as $upazila) {
                fputcsv($handle, [
                    $upazila->id,
                    $upazila->name,
                    $upazila->district?->name,
                    $upazila->district?->division?->name,
                    $upazila->status,
                    $upazila->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
