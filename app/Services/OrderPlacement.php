<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Transaction;
use App\Support\Cart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a product cart into an Order through the exact same pipeline the order
 * API uses (see Api\V1\OrderController::storeSingleType) — client-submitted
 * prices are never trusted, every line is recomputed from the product, coupons
 * pass through the same isValidFor/appliesToProduct/discountFor rules, and the
 * order + items + transaction + coupon usage counter are persisted atomically.
 */
class OrderPlacement
{
    /**
     * @param  array<int, array{
     *     product_id: int,
     *     quantity: int,
     *     attributes?: array<string, string>,
     * }>  $items
     * @param  array{
     *     customer_name: string,
     *     customer_email: string,
     *     customer_phone: string,
     *     shipping_address?: ?string,
     *     payment_method: string,
     *     notes?: ?string,
     * }  $customer
     */
    public function placeProducts(array $items, array $customer, ?string $couponCode = null, ?int $shippingMethodId = null): Order
    {
        $rawItems = collect($items);

        $products = Product::whereIn('id', $rawItems->pluck('product_id')->filter())
            ->get()
            ->keyBy('id');

        $lines = $rawItems->map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);
            $quantity = min(Cart::MAX_QUANTITY, max(1, (int) $item['quantity']));
            $attributes = array_map('strval', $item['attributes'] ?? []);

            $isVariant = $attributes !== [];
            $variation = $isVariant ? $product->variationRow($attributes) : null;

            // A chosen combination that's no longer in the catalog must not
            // silently degrade to the base product — fail the placement so the
            // shopper sees it and re-picks.
            if ($isVariant && $variation === null) {
                throw ValidationException::withMessages([
                    'items' => "The selected options for {$product->getTranslation('name', 'en', false)} are no longer available.",
                ]);
            }

            $unitPrice = $isVariant ? $product->variationPrice($attributes) : (float) $product->price;
            $discountPrice = $isVariant ? $product->variationDiscount($attributes) : ($product->hasDiscount() ? (float) $product->discount_price : null);

            return [
                'type' => 'product',
                'product_id' => $product->id,
                'service_id' => null,
                'item_name' => $product->getTranslation('name', 'en', false),
                // The combination's own sku when the product uses options,
                // else the base product sku — either way the receipt records
                // exactly which unit was ordered.
                'sku' => ($isVariant && filled($variation['sku'] ?? null)) ? $variation['sku'] : ($product->sku ?? null),
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => round(($discountPrice ?? $unitPrice) * $quantity, 2),
                'variations' => $isVariant ? $attributes : null,
                'is_discounted' => $discountPrice !== null,
            ];
        });

        $subtotal = round($lines->sum('line_total'), 2);
        $currency = (string) Setting::get('currency_code', 'BDT');

        [$couponCode, $discount] = $this->applyCoupon($couponCode, $subtotal, $lines);

        $taxable = round($subtotal - $discount, 2);
        $vat = Setting::vatFor($taxable);
        $vatRate = Setting::vatEnabled() ? Setting::vatRate() : null;

        // A selected shipping method is resolved server-side — the client never
        // gets to name its price. Cost is snapshotted onto the order so a later
        // price change can't rewrite history.
        [$shippingMethod, $shippingCost] = $this->shippingSnapshot($shippingMethodId);

        $total = round($taxable + $vat + $shippingCost, 2);

        return DB::transaction(function () use ($customer, $lines, $subtotal, $couponCode, $discount, $vat, $vatRate, $shippingMethod, $shippingCost, $total, $currency) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $customer['customer_phone'],
                'shipping_address' => $customer['shipping_address'] ?? null,
                'payment_method' => $customer['payment_method'],
                'currency' => $currency,
                'subtotal' => $subtotal,
                'coupon_code' => $couponCode !== '' ? $couponCode : null,
                'discount' => $discount,
                'vat_amount' => $vat,
                'vat_rate' => $vatRate,
                'shipping_method' => $shippingMethod,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'notes' => $customer['notes'] ?? null,
            ]);

            $order->items()->createMany($lines->all());

            if ($couponCode !== '') {
                Coupon::where('code', $couponCode)->increment('used_count');
            }

            Transaction::create([
                'order_id' => $order->id,
                'payment_method' => $customer['payment_method'],
                'amount' => $total,
                'currency' => $currency,
            ]);

            return $order;
        });
    }

    /**
     * Resolves an active shipping method's name + cost for the order snapshot.
     * The cost always comes from the table, never the client. Returns [null,
     * 0.0] when no method was chosen — a storefront with no active methods (or
     * an order with nothing physical to ship) simply carries no shipping fee.
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
     * Validates a coupon_code against the cart and returns [code, discount] —
     * code is '' when none was given. Mirrors OrderController::applyCoupon() so
     * the storefront checkout and the order API agree on every coupon rule.
     *
     * @return array{0: string, 1: float}
     */
    private function applyCoupon(?string $couponCode, float $subtotal, Collection $lines): array
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

        $hasDiscountedItem = $lines->contains(fn (array $line) => $line['is_discounted']);

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
}
