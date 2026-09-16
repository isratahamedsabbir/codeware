<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostCategoryExportController extends Controller
{
    /**
     * Streams the given category ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Post Categories screen
     * (see Livewire\Admin\PostCategories\Index::$selectedIds), so this
     * always exports an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $categories = PostCategory::query()
            ->with('creator')
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $filename = 'post-categories-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($categories) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name (EN)', 'Name (BN)', 'Slug', 'Status', 'Created By', 'Created At']);

            foreach ($categories as $category) {
                fputcsv($handle, [
                    $category->id,
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
