<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Transaction;
use App\Support\PaymentMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Placing an order. Products only — a service is booked (App\Models\Booking,
 * see POST /api/v1/bookings), never bought, so every line on an order is a
 * product and there is no per-item type to send or receive.
 */
class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless((bool) Setting::get('shop_enabled', true), 503, 'The shop is currently closed for new orders.');

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'shipping_address' => 'required|string|max:2000',
            'shipping_method_id' => ['nullable', 'integer', Rule::exists('shipping_methods', 'id')->where('status', 'active')],
            'payment_method' => ['required', 'string', Rule::in(array_keys(PaymentMethods::available()))],
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active')->where('is_upcoming', false),
            ],
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ]);

        $products = Product::whereIn('id', collect($validated['items'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $lines = collect($validated['items'])->map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->price;

            return [
                'product_id' => $product->id,
                'item_name' => $product->getTranslation('name', 'en', false),
                'sku' => $product->sku,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => round($unitPrice * $quantity, 2),
            ];
        });

        $subtotal = round($lines->sum('line_total'), 2);
        $currency = (string) Setting::get('currency_code', 'BDT');

        [$couponCode, $discount] = $this->applyCoupon($validated['coupon_code'] ?? null, $subtotal, $lines, $products);

        $taxable = round($subtotal - $discount, 2);
        $vat = Setting::vatFor($taxable);
        $vatRate = Setting::vatEnabled() ? Setting::vatRate() : null;

        [$shippingMethod, $shippingCost] = $this->shippingSnapshot($validated['shipping_method_id'] ?? null);
        $total = round($taxable + $vat + $shippingCost, 2);

        $order = $this->persistOrder($validated, $lines, $subtotal, $couponCode, $discount, $vat, $vatRate, $shippingMethod, $shippingCost, $total, $currency);

        return response()->json([
            'data' => $this->formatOrder($order->load('items')),
        ], 201);
    }

    /**
     * Validates a coupon_code against the cart and returns [code, discount] —
     * code is '' when none was given.
     *
     * @return array{0: string, 1: float}
     */
    private function applyCoupon(?string $couponCode, float $subtotal, Collection $lines, Collection $products): array
    {
        $couponCode = trim(strtoupper((string) $couponCode));

        if ($couponCode === '') {
            return ['', 0.0];
        }

        $coupon = Coupon::where('code', $couponCode)->first();

        if (! $coupon || ! $coupon->isValidFor($subtotal)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'The coupon code is invalid or no longer available.',
            ]);
        }

        $hasDiscountedItem = $lines->contains(fn (array $line) => ($product = $products->get($line['product_id'])) !== null
            && $product->hasDiscount());

        if ($hasDiscountedItem) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon cannot be used with discounted products.',
            ]);
        }

        $hasUncoveredItem = $lines->contains(fn (array $line) => ! $coupon->appliesToProduct($line['product_id']));

        if ($hasUncoveredItem) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon does not apply to one or more items in your cart.',
            ]);
        }

        return [$couponCode, $coupon->discountFor($subtotal)];
    }

    /**
     * Resolves an active shipping method's name + cost for the order snapshot.
     * The cost comes from the table, never the client. Null/0.00 when the
     * customer picks no method.
     *
     * @return array{0: ?string, 1: float}
     */
    private function shippingSnapshot(?int $shippingMethodId): array
    {
        if ($shippingMethodId === null) {
            return [null, 0.0];
        }

        $method = ShippingMethod::active()->find($shippingMethodId);

        if (! $method) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'The selected shipping method is no longer available.',
            ]);
        }

        return [$method->name, (float) $method->cost];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistOrder(array $validated, Collection $lines, float $subtotal, string $couponCode, float $discount, float $vat, ?float $vatRate, ?string $shippingMethod, float $shippingCost, float $total, string $currency): Order
    {
        return DB::transaction(function () use ($validated, $lines, $subtotal, $couponCode, $discount, $vat, $vatRate, $shippingMethod, $shippingCost, $total, $currency) {
            $order = Order::create([
                'user_id' => auth('sanctum')->id(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'] ?? null,
                'payment_method' => $validated['payment_method'],
                'currency' => $currency,
                'subtotal' => $subtotal,
                'coupon_code' => $couponCode !== '' ? $couponCode : null,
                'discount' => $discount,
                'vat_amount' => $vat,
                'vat_rate' => $vatRate,
                'shipping_method' => $shippingMethod,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->items()->createMany($lines->all());

            if ($couponCode !== '') {
                Coupon::where('code', $couponCode)->increment('used_count');
            }

            Transaction::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'amount' => $total,
                'currency' => $currency,
            ]);

            return $order;
        });
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $order = Order::with(['items', 'transactions'])
            ->where('order_number', $orderNumber)
            ->where('customer_email', $validated['email'])
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatOrder($order, withTransactions: true),
        ]);
    }

    private function formatOrder(Order $order, bool $withTransactions = false): array
    {
        $data = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'currency' => $order->currency,
            'subtotal' => (float) $order->subtotal,
            'coupon_code' => $order->coupon_code,
            'discount' => (float) $order->discount,
            'vat_amount' => (float) $order->vat_amount,
            'vat_rate' => $order->vat_rate !== null ? (float) $order->vat_rate : null,
            'shipping_method' => $order->shipping_method,
            'shipping_cost' => (float) $order->shipping_cost,
            'total' => (float) $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
            'created_at_display' => $order->created_at?->toDisplay(),
            'items' => $order->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'sku' => $item->sku,
                'variations' => $item->variations ?: null,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->line_total,
            ]),
        ];

        if ($withTransactions) {
            $data['transactions'] = $order->transactions->map(fn ($t) => [
                'reference' => $t->reference,
                'payment_method' => $t->payment_method,
                'amount' => (float) $t->amount,
                'status' => $t->status,
                'paid_at' => $t->paid_at?->toIso8601String(),
                'paid_at_display' => $t->paid_at?->toDisplay(),
            ]);
        }

        return $data;
    }
}
