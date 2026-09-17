<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductBrandExportController extends Controller
{
    /**
     * Streams the given brand ids as CSV — triggered by the "Export" button
     * in the bulk-selection toolbar on the Product Brands screen (see
     * Livewire\Admin\ProductBrands\Index::$selectedIds), so this always
     * exports an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $brands = ProductBrand::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('name->en')
            ->get();

        $filename = 'product-brands-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($brands) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name (EN)', 'Name (BN)', 'Status', 'Created At']);

            foreach ($brands as $brand) {
                fputcsv($handle, [
                    $brand->id,
                    $brand->getTranslation('name', 'en', false),
                    $brand->getTranslation('name', 'bn', false),
                    $brand->status,
                    $brand->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
