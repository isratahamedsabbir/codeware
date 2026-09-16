<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductAttributeExportController extends Controller
{
    /**
     * Streams the given attribute ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Product Attributes screen
     * (see Livewire\Admin\ProductAttributes\Index::$selectedIds), so this
     * always exports an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $attributes = ProductAttribute::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        $filename = 'product-attributes-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($attributes) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'Values', 'Created At']);

            foreach ($attributes as $attribute) {
                fputcsv($handle, [
                    $attribute->id,
                    $attribute->name,
                    implode(', ', $attribute->values ?? []),
                    $attribute->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
