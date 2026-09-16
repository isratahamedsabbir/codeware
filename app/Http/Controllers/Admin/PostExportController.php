<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExportController extends Controller
{
    /**
     * Streams the given post ids as CSV — triggered by the "Export" button
     * in the bulk-selection toolbar on the Posts screen (see
     * Livewire\Admin\Posts\Index::$selectedIds), so this always exports an
     * explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $posts = Post::query()
            ->with('category')
            ->whereIn('id', $ids)
            ->latest()
            ->get();

        $filename = 'posts-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($posts) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Title (EN)', 'Title (BN)', 'Slug', 'Category', 'Status', 'Published At', 'Created At']);

            foreach ($posts as $post) {
                fputcsv($handle, [
                    $post->id,
                    $post->getTranslation('title', 'en', false),
                    $post->getTranslation('title', 'bn', false),
                    $post->slug,
                    $post->category?->getTranslation('name', 'en', false),
                    $post->status,
                    $post->published_at?->toDateTimeString(),
                    $post->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
