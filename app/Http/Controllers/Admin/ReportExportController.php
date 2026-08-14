<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * Streams the orders report as CSV, honoring whatever filters are currently
     * active on the Reports screen.
     */
    public function export(Request $request): StreamedResponse
    {
        $status = (string) $request->query('status', '');
        $paymentStatus = (string) $request->query('payment_status', '');
        $paymentMethod = (string) $request->query('payment_method', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $orders = Order::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($paymentStatus, fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($paymentMethod, fn ($q) => $q->where('payment_method', $paymentMethod))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->get();

        $filename = 'orders-report-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Order #', 'Customer', 'Email', 'Payment Method', 'Payment Status', 'Order Status', 'Subtotal', 'Total', 'Currency', 'Placed At']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_email,
                    $order->payment_method,
                    $order->payment_status,
                    $order->status,
                    $order->subtotal,
                    $order->total,
                    $order->currency,
                    $order->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
