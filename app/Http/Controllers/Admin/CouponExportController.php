<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponExportController extends Controller
{
    /**
     * Streams the given coupon ids as CSV — triggered by the "Export" button
     * in the bulk-selection toolbar on the Coupons screen (see
     * Livewire\Admin\Coupons\Index::$selectedIds), so this always exports an
     * explicit selection rather than the whole table.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = array_map('intval', (array) $request->query('ids', []));

        $coupons = Coupon::query()
            ->whereIn('id', $ids)
            ->orderByDesc('id')
            ->get();

        $filename = 'coupons-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($coupons) {
            $handle = fopen('php://output', 'w');

            // A UTF-8 BOM so Excel doesn't mangle non-Latin (e.g. Bengali) text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Code', 'Type', 'Value', 'Min Order Amount', 'Max Uses', 'Used Count', 'Expires At', 'Status', 'Created At']);

            foreach ($coupons as $coupon) {
                fputcsv($handle, [
                    $coupon->id,
                    $coupon->code,
                    $coupon->type,
                    $coupon->value,
                    $coupon->min_order_amount,
                    $coupon->max_uses,
                    $coupon->used_count,
                    $coupon->expires_at?->toDateTimeString(),
                    $coupon->status,
                    $coupon->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
