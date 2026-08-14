<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportController extends Controller
{
    /**
     * Streams the product list as CSV, honoring whatever search/status filter is
     * currently active on the Products screen — an export button click carries
     * those over as query params rather than always exporting everything.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');

        $products = Product::query()
            ->with('category')
            ->when($search, fn ($q) => $q
                ->where('name->en', 'like', "%{$search}%")
                ->orWhere('name->bn', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $filename = 'products-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name (EN)', 'Name (BN)', 'Slug', 'Category', 'Price', 'Status', 'Featured', 'Sort Order', 'Created At']);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->id,
                    $product->getTranslation('name', 'en', false),
                    $product->getTranslation('name', 'bn', false),
                    $product->slug,
                    $product->category?->getTranslation('name', 'en', false),
                    $product->price,
                    $product->status,
                    $product->is_featured ? 'Yes' : 'No',
                    $product->sort_order,
                    $product->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
