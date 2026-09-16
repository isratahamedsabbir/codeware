<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVendor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductVendorExportController extends Controller
{
    /**
     * Streams the given vendor ids as CSV — triggered by the "Export" button
     * in the bulk-selection toolbar on the Product Vendors screen (see
     * Livewire\Admin\ProductVendors\Index::$selectedIds), so this always
     * exports an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $vendors = ProductVendor::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $filename = 'product-vendors-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($vendors) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'Mobile', 'Email', 'Address', 'Status', 'Created At']);

            foreach ($vendors as $vendor) {
                fputcsv($handle, [
                    $vendor->id,
                    $vendor->name,
                    $vendor->mobile,
                    $vendor->email,
                    $vendor->address,
                    $vendor->status,
                    $vendor->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
