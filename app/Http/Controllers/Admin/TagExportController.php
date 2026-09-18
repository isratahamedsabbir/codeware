<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagExportController extends Controller
{
    /**
     * Streams the given tag ids as CSV — triggered by the "Export" button in
     * the bulk-selection toolbar on the Tags screen (see
     * Livewire\Admin\Tags\Index::$selectedIds), so this always exports an
     * explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $tags = Tag::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        $filename = 'tags-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($tags) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name (EN)', 'Name (BN)', 'Status', 'Created At']);

            foreach ($tags as $tag) {
                fputcsv($handle, [
                    $tag->id,
                    $tag->getTranslation('name', 'en', false),
                    $tag->getTranslation('name', 'bn', false),
                    $tag->status,
                    $tag->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
