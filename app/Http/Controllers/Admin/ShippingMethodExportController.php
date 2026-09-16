<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShippingMethodExportController extends Controller
{
    /**
     * Streams the given shipping method ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Shipping Methods screen (see
     * Livewire\Admin\ShippingMethods\Index::$selectedIds), so this always
     * exports an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $methods = ShippingMethod::query()
            ->whereIn('id', $ids)
            ->orderByDesc('id')
            ->get();

        $filename = 'shipping-methods-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($methods) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'Cost', 'Status', 'Created At']);

            foreach ($methods as $method) {
                fputcsv($handle, [
                    $method->id,
                    $method->name,
                    $method->cost,
                    $method->status,
                    $method->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
