<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactExportController extends Controller
{
    /**
     * Streams the given contact ids as CSV — triggered by the "Export"
     * button in the bulk-selection toolbar on the Contacts screen (see
     * Livewire\Admin\Contacts\Index::$selectedIds), so this always exports
     * an explicit selection rather than the whole table. Contacts has no
     * delete action (deliberately read-only), so Export is the only bulk
     * action this screen gets.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $contacts = Contact::query()
            ->whereIn('id', $ids)
            ->latest()
            ->get();

        $filename = 'contacts-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Status', 'Created At']);

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->id,
                    $contact->full_name,
                    $contact->email,
                    $contact->phone_number,
                    $contact->subject,
                    $contact->message,
                    $contact->status,
                    $contact->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
