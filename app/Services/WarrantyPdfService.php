<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\PrintAssets;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Collection;

/**
 * Renders the warranty card PDF for an order — one card per line item whose
 * product carries a warranty (see Product::hasWarranty()). Mirrors
 * InvoiceController/VoucherPdfService's DomPDF recipe.
 */
class WarrantyPdfService
{
    /**
     * @return Collection<int, OrderItem>
     */
    public function eligibleItems(Order $order): Collection
    {
        $order->loadMissing('items.product');

        return $order->items
            ->filter(fn ($item) => $item->type === 'product' && $item->product?->hasWarranty())
            ->values();
    }

    public function make(Order $order): PdfDocument
    {
        $items = $this->eligibleItems($order);

        abort_if($items->isEmpty(), 404, 'This order has no warranty-eligible items.');

        return Pdf::loadView('warranties.show', [
            'order' => $order,
            'items' => $items,
            'logo' => PrintAssets::logoDataUri(),
        ])->setPaper('a4');
    }

    public function fileName(Order $order): string
    {
        return "warranty-{$order->order_number}.pdf";
    }
}
