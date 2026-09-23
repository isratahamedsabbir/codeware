<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\ShippingMethod;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    private const PAYMENT_METHODS = ['cod', 'cod', 'cod', 'paypal', 'stripe', 'bkash'];

    public function run(): void
    {
        $products = Product::query()->active()->get();

        if ($products->isEmpty()) {
            $products = Product::factory()->published()->count(10)->create();
        }

        $services = Service::query()->active()->get();

        if ($services->isEmpty()) {
            $services = Service::factory()->published()->count(6)->create();
        }

        Order::query()->delete();

        collect(range(1, 100))->each(function () use ($products, $services) {
            $status = fake()->randomElement(Order::STATUSES);
            $paymentStatus = $this->paymentStatusFor($status);
            $paymentMethod = fake()->randomElement(self::PAYMENT_METHODS);

            // Weighted so most demo orders are product-only (the common case),
            // with a mix of service-only and mixed carts to exercise the
            // Product/Service/Mixed badge on the admin Orders list.
            $cartType = fake()->randomElement(['product', 'product', 'product', 'service', 'mixed']);

            $productLines = $cartType === 'service'
                ? collect()
                : $products->random(min(4, $products->count()))
                    ->take(fake()->numberBetween(1, 3))
                    ->map(fn (Product $product) => $this->productLine($product));

            $serviceLines = $cartType === 'product' || $services->isEmpty()
                ? collect()
                : $services->random(min(3, $services->count()))
                    ->take(fake()->numberBetween(1, 2))
                    ->map(fn (Service $service) => $this->serviceLine($service));

            $lines = $productLines->concat($serviceLines);

            $subtotal = round($lines->sum('line_total'), 2);

            // Product orders carry a shipping snapshot — a random active method
            // where there is one, else no shipping fee at all. The demo data
            // mirrors the OderPlacement total: subtotal + shipping.
            $shipping = $productLines->isNotEmpty() && ShippingMethod::active()->exists()
                ? ShippingMethod::active()->inRandomOrder()->first()
                : null;

            $order = Order::create([
                'customer_name' => fake()->name(),
                'customer_email' => fake()->safeEmail(),
                'customer_phone' => fake()->numerify('01#########'),
                // A service-only cart has nothing to deliver (see
                // OrderController::store()'s same conditional rule).
                'shipping_address' => $productLines->isNotEmpty() ? fake()->address() : null,
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
    private function productLine(Product $product): array
    {
        $unitPrice = (float) $product->price ?: fake()->randomFloat(2, 100, 3000);
        $quantity = fake()->numberBetween(1, 4);

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

    /**
     * @return array<string, mixed>
     */
    private function serviceLine(Service $service): array
    {
        $unitPrice = (float) $service->price ?: fake()->randomFloat(2, 100, 3000);
        $quantity = fake()->numberBetween(1, 2);

        return [
            'product_id' => null,
            'service_id' => $service->id,
            'type' => 'service',
            'item_name' => $service->getTranslation('name', 'en', false),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => round($unitPrice * $quantity, 2),
        ];
    }
}
