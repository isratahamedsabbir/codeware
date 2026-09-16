<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberExportController extends Controller
{
    /**
     * Streams the given subscriber ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Subscribers screen (see
     * Livewire\Admin\Subscribers\Index::$selectedIds), so this always
     * exports an explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $subscribers = Subscriber::query()
            ->whereIn('id', $ids)
            ->latest()
            ->get();

        $filename = 'subscribers-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($subscribers) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Email', 'Status', 'Created At']);

            foreach ($subscribers as $subscriber) {
                fputcsv($handle, [
                    $subscriber->id,
                    $subscriber->email,
                    $subscriber->status,
                    $subscriber->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
