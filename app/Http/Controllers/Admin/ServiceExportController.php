<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceExportController extends Controller
{
    /**
     * Streams the given service ids as CSV — triggered by the "Export" button
     * in the bulk-selection toolbar on the Services screen (see
     * Livewire\Admin\Services\Index::$selectedIds), so this always exports
     * an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $services = Service::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $filename = 'services-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($services) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name (EN)', 'Name (BN)', 'Slug', 'Price', 'Status', 'Created At']);

            foreach ($services as $service) {
                fputcsv($handle, [
                    $service->id,
                    $service->getTranslation('name', 'en', false),
                    $service->getTranslation('name', 'bn', false),
                    $service->slug,
                    $service->price,
                    $service->status,
                    $service->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
