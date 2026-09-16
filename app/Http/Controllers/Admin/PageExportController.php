<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageExportController extends Controller
{
    /**
     * Streams the given page ids as CSV — triggered by the "Export" button in
     * the bulk-selection toolbar on the Pages screen (see
     * Livewire\Admin\Pages\Index::$selectedIds), so this always exports an
     * explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $pages = Page::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $filename = 'pages-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($pages) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Title (EN)', 'Title (BN)', 'Slug', 'Type', 'Template', 'Status', 'Created At']);

            foreach ($pages as $page) {
                fputcsv($handle, [
                    $page->id,
                    $page->getTranslation('title', 'en', false),
                    $page->getTranslation('title', 'bn', false),
                    $page->slug,
                    $page->type,
                    $page->template,
                    $page->status,
                    $page->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
