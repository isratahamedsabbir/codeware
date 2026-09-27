<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo orders. Products only — an order is always something physical, so
 * there is no cart shape to vary here. A service request is a booking
 * (App\Models\Booking), a separate table with no price or payment on it, and
 * deliberately not seeded here.
 */
class OrderSeeder extends Seeder
{
    private const PAYMENT_METHODS = ['cod', 'cod', 'cod', 'paypal', 'stripe', 'bkash'];

    public function run(): void
    {
        $products = Product::query()->active()->get();

        if ($products->isEmpty()) {
            $products = Product::factory()->published()->count(10)->create();
        }

        Order::query()->delete();

        collect(range(1, 100))->each(function () use ($products) {
            $status = fake()->randomElement(Order::STATUSES);
            $paymentStatus = $this->paymentStatusFor($status);
            $paymentMethod = fake()->randomElement(self::PAYMENT_METHODS);

            $lines = $products->random(min(4, $products->count()))
                ->take(fake()->numberBetween(1, 3))
                ->map(fn (Product $product) => $this->line($product));

            $subtotal = round($lines->sum('line_total'), 2);

            // A random active shipping method where there is one, else the order
            // is collected in person and carries no shipping fee. The demo data
            // mirrors the OrderController total: subtotal + shipping.
            $shipping = ShippingMethod::active()->exists()
                ? ShippingMethod::active()->inRandomOrder()->first()
                : null;

            $order = Order::create([
                'customer_name' => fake()->name(),
                'customer_email' => fake()->safeEmail(),
                'customer_phone' => fake()->numerify('01#########'),
                'shipping_address' => fake()->address(),
                'status' => $status,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'currency' => 'BDT',
                'subtotal' => $subtotal,
                'shipping_method' => $shipping?->name,
                'shipping_cost' => $shipping?->cost ?? 0,
                'total' => round($subtotal + (float) ($shipping?->cost ?? 0), 2),
                'notes' => fake()->boolean(20) ? fake()->sentence() : null,
                'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
            ]);

            $order->items()->createMany($lines->all());

            Transaction::create([
                'order_id' => $order->id,
                'reference' => 'TXN-'.strtoupper(Str::random(10)),
                'payment_method' => $paymentMethod,
                'amount' => $order->total,
                'currency' => 'BDT',
                'status' => match ($paymentStatus) {
                    'paid' => 'success',
                    'failed' => 'failed',
                    'refunded' => 'refunded',
                    default => 'pending',
                },
                'paid_at' => $paymentStatus === 'paid' ? $order->created_at : null,
            ]);
        });
    }

    private function paymentStatusFor(string $orderStatus): string
    {
        return match ($orderStatus) {
            'delivered' => fake()->randomElement(['paid', 'paid', 'paid', 'refunded']),
            'cancelled' => fake()->randomElement(['failed', 'refunded', 'pending']),
            'shipped', 'processing' => fake()->randomElement(['paid', 'paid', 'pending']),
            default => 'pending',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function line(Product $product): array
    {
        $unitPrice = (float) $product->price ?: fake()->randomFloat(2, 100, 3000);
        $quantity = fake()->numberBetween(1, 4);

        return [
            'product_id' => $product->id,
            'item_name' => $product->getTranslation('name', 'en', false),
            'sku' => $product->sku,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => round($unitPrice * $quantity, 2),
        ];
    }
}
