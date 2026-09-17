<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Transaction;
use App\Support\PaymentMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless((bool) Setting::get('shop_enabled', true), 503, 'The shop is currently closed for new orders.');

        $rawItems = collect($request->input('items', []));

        // Delivery only matters when the cart has something physical in it —
        // a non-empty, product-free cart (services only) needs no address.
        // An empty/missing items list falls back to "required" so the plain
        // required-field validation error still surfaces below.
        $shippingRequired = $rawItems->isEmpty()
            || $rawItems->contains(fn ($item) => ! empty($item['product_id'] ?? null));

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'shipping_address' => ($shippingRequired ? 'required' : 'nullable').'|string|max:2000',
            'payment_method' => ['required', 'string', Rule::in(array_keys(PaymentMethods::available()))],
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*' => [
                function (string $attribute, mixed $value, \Closure $fail) {
                    $hasProduct = ! empty($value['product_id'] ?? null);
                    $hasService = ! empty($value['service_id'] ?? null);

                    if ($hasProduct === $hasService) {
                        $fail('Each item must have exactly one of product_id or service_id.');
                    }
                },
            ],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active')->where('is_upcoming', false),
            ],
            'items.*.service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('status', 'active'),
            ],
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ]);

        $products = Product::whereIn('id', collect($validated['items'])->pluck('product_id')->filter())
            ->get()
            ->keyBy('id');

        $services = Service::whereIn('id', collect($validated['items'])->pluck('service_id')->filter())
            ->get()
            ->keyBy('id');

        $lines = collect($validated['items'])->map(function (array $item) use ($products, $services) {
            $quantity = (int) $item['quantity'];

            if (! empty($item['product_id'] ?? null)) {
                $product = $products->get($item['product_id']);
                $unitPrice = (float) $product->price;

                return [
                    'product_id' => $product->id,
                    'service_id' => null,
                    'type' => 'product',
                    'item_name' => $product->getTranslation('name', 'en', false),
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => round($unitPrice * $quantity, 2),
                ];
            }

            $service = $services->get($item['service_id']);
            $unitPrice = (float) $service->price;

            return [
                'product_id' => null,
                'service_id' => $service->id,
                'type' => 'service',
                'item_name' => $service->getTranslation('name', 'en', false),
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => round($unitPrice * $quantity, 2),
            ];
        });

        $subtotal = round($lines->sum('line_total'), 2);
        $currency = (string) Setting::get('currency_code', 'BDT');

        // Coupon handling — a coupon never stacks on top of a product sale. Any
        // item already carrying a discount price disqualifies the whole coupon,
        // and the buyer is told why (422 surfaces as an alert on the client).
        $coupon = null;
        $discount = 0.0;

        $couponCode = trim(strtoupper((string) ($validated['coupon_code'] ?? '')));

        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();

            if (! $coupon || ! $coupon->isValidFor($subtotal)) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'The coupon code is invalid or no longer available.',
                ]);
            }

            $hasDiscountedItem = $lines->contains(fn (array $line) => $line['type'] === 'product'
                && ($product = $products->get($line['product_id'])) !== null
                && $product->hasDiscount());

            if ($hasDiscountedItem) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'This coupon cannot be used with discounted products.',
                ]);
            }

            $hasUncoveredItem = $lines->contains(fn (array $line) => $line['type'] === 'product'
                && ! $coupon->appliesToProduct($line['product_id']));

            if ($hasUncoveredItem) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'This coupon does not apply to one or more items in your cart.',
                ]);
            }

            $discount = $coupon->discountFor($subtotal);
        }

        $total = round($subtotal - $discount, 2);

        $order = DB::transaction(function () use ($validated, $lines, $subtotal, $couponCode, $discount, $total, $currency) {
            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'] ?? null,
                'payment_method' => $validated['payment_method'],
                'currency' => $currency,
                'subtotal' => $subtotal,
                'coupon_code' => $couponCode !== '' ? $couponCode : null,
                'discount' => $discount,
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

        return response()->json([
            'data' => $this->formatOrder($order->load('items')),
        ], 201);
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
            'total' => (float) $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
            'created_at_display' => $order->created_at?->toDisplay(),
            'items' => $order->items->map(fn ($item) => [
                'type' => $item->type,
                'item_name' => $item->item_name,
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
