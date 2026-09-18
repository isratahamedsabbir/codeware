<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\WarrantyPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the warranty card PDF for an order's warranty-eligible product
 * items — from the admin order page, and publicly (order number + the
 * customer's own email, no login) for the same reasoning as
 * OrderController::show(). Mirrors VoucherController/InvoiceController.
 */
class WarrantyController extends Controller
{
    public function __construct(
        private readonly WarrantyPdfService $pdf,
    ) {}

    public function adminDownload(Request $request, Order $order): Response
    {
        return $this->pdf->make($order)->download($this->pdf->fileName($order));
    }

    public function publicDownload(Request $request, string $orderNumber): Response
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $order = Order::where('order_number', $orderNumber)
            ->where('customer_email', $validated['email'])
            ->firstOrFail();

        return $this->pdf->make($order)->download($this->pdf->fileName($order));
    }
}
