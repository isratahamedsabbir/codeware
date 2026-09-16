<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DivisionExportController extends Controller
{
    /**
     * Streams the given division ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Divisions screen (see
     * Livewire\Admin\Divisions\Index::$selectedIds), so this always exports
     * an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $divisions = Division::query()
            ->with('country')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        $filename = 'divisions-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($divisions) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'Country', 'Status', 'Created At']);

            foreach ($divisions as $division) {
                fputcsv($handle, [
                    $division->id,
                    $division->name,
                    $division->country?->name,
                    $division->status,
                    $division->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
