<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryExportController extends Controller
{
    /**
     * Streams the given category ids as CSV — triggered by the "Export" button
     * in the bulk-selection toolbar on the Categories screen (see
     * Livewire\Admin\Categories\Index::$selectedIds), so this always exports
     * an explicit selection rather than the whole table. Ids can span both
     * types (Product/Post categories share one table), so Type is included.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $categories = Category::query()
            ->with('creator')
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $filename = 'categories-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($categories) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Type', 'Name (EN)', 'Name (BN)', 'Slug', 'Status', 'Created By', 'Created At']);

            foreach ($categories as $category) {
                fputcsv($handle, [
                    $category->id,
                    $category->type === Category::TYPE_PRODUCT ? 'Product' : 'Post',
                    $category->getTranslation('name', 'en', false),
                    $category->getTranslation('name', 'bn', false),
                    $category->slug,
                    $category->status,
                    $category->creator?->name,
                    $category->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
