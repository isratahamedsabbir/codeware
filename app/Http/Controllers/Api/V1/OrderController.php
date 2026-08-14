<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Support\PaymentMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'shipping_address' => 'required|string|max:2000',
            'payment_method' => ['required', 'string', Rule::in(array_keys(PaymentMethods::available()))],
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active'),
            ],
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ]);

        $products = Product::whereIn('id', collect($validated['items'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $lines = collect($validated['items'])->map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);
            $unitPrice = (float) $product->price;
            $quantity = (int) $item['quantity'];

            return [
                'product_id' => $product->id,
                'product_name' => $product->getTranslation('name', 'en', false),
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => round($unitPrice * $quantity, 2),
            ];
        });

        $subtotal = round($lines->sum('line_total'), 2);
        $currency = (string) Setting::get('currency_code', 'BDT');

        $order = DB::transaction(function () use ($validated, $lines, $subtotal, $currency) {
            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'],
                'payment_method' => $validated['payment_method'],
                'currency' => $currency,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->items()->createMany($lines->all());

            Transaction::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'amount' => $subtotal,
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
            'total' => (float) $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
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
            ]);
        }

        return $data;
    }
}
