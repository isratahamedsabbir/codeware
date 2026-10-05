<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Cart;
use App\Support\Referral;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns a product cart into an Order through the exact same pipeline the order
 * API uses (see Api\V1\OrderController::storeSingleType) — client-submitted
 * prices are never trusted, every line is recomputed from the product, coupons
 * pass through the same isValidFor/appliesToProduct/discountFor rules, and the
 * order + items + transaction + coupon usage counter are persisted atomically.
 *
 * Every order ends up owned by a user, including one placed by a guest: the
 * account is looked up by the submitted email, and created when there isn't one
 * yet (see resolveGuestBuyer), so the storefront and the API can both be open
 * to guests without any order ever ending up ownerless.
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
            $discountPrice = $product->effectiveDiscount($attributes);

            return [
                'product_id' => $product->id,
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

        $signedInId = auth()->id();

        // Read before the transaction and only cleared after it commits, rather
        // than consumed as part of the write: a failure anywhere below rolls the
        // order back, and the shopper is about to retry it — dropping the
        // attribution on that retry would lose the referral permanently.
        $referrerId = Referral::idFor($signedInId);

        // Resolved inside the transaction, not before it: a guest's account is a
        // row written there, and it has to roll back with the order if anything
        // else fails — otherwise a shopper who abandons a failed checkout would
        // be left with an account they never made. Declared out here by
        // reference so the committed result can be read afterwards, which is
        // what decides whether an account-claim email is owed.
        $buyer = ['id' => $signedInId, 'created' => false, 'email' => null];

        $order = DB::transaction(function () use ($customer, $lines, $subtotal, $couponCode, $discount, $vat, $vatRate, $shippingMethod, $shippingCost, $total, $currency, $signedInId, $referrerId, &$buyer) {
            $buyer = $this->resolveBuyer($customer, $signedInId);

            $order = Order::create([
                'user_id' => $buyer['id'],
                'ref' => $referrerId,
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

        // Only now that the order is committed and cannot be rolled back: the
        // account this order created has to be claimable, and a mail saying so
        // is the customer's only route back to the order history.
        if ($buyer['created'] && $buyer['email'] !== null) {
            $this->sendAccountClaimEmail($buyer['email']);
        }

        // The link has done its job. A second order placed in the same session
        // is the buyer's own, not their referrer's.
        Referral::forget();

        return $order;
    }

    /**
     * The account that owns this order.
     *
     * A signed-in shopper always owns their own order, whatever email the form
     * carried — the session decides, not the form, so a shopper can't hand an
     * order to somebody else's account by typing it. A guest's order is
     * attached to the account holding the email they typed, which is created
     * when there isn't one yet.
     *
     * @param  array{customer_name: string, customer_email: string}  $customer
     * @return array{id: int, created: bool, email: ?string}
     */
    private function resolveBuyer(array $customer, ?int $signedInId): array
    {
        return $signedInId !== null
            ? ['id' => $signedInId, 'created' => false, 'email' => null]
            : $this->resolveGuestBuyer($customer);
    }

    /**
     * The account that owns a guest's order, created from the submitted email
     * when nobody is signed up under it yet.
     *
     * Checkout is open to guests (see the /checkout route), so an order can
     * arrive with nobody attached to it. Creating the account from the typed
     * email means the order is owned the moment it exists, rather than waiting
     * for the customer to register and hoping the two are matched up later by
     * Order::scopeForCustomer() — the history is theirs from the start, and the
     * invoice, warranty card and account pages all work off that same link.
     *
     * The password is a random string nobody is ever told, so the account
     * cannot be signed into: the real one is set by redeeming the code mailed
     * out by sendAccountClaimEmail(), the same way a forgotten one would be.
     * Roles are left empty, so a self-registered-looking row here has no admin,
     * staff, vendor or delivery power — every one of those is granted from the
     * admin panel only.
     *
     * @param  array{customer_name: string, customer_email: string}  $customer
     * @return array{id: int, created: bool, email: string}
     */
    private function resolveGuestBuyer(array $customer): array
    {
        // User::email() lowercases on write, so the lookup is lowered too —
        // otherwise a guest typing "Jane@Example.com" would miss an account
        // stored as "jane@example.com" and try to create a duplicate of it,
        // which the unique index would reject.
        $email = Str::lower(trim((string) $customer['customer_email']));

        $user = User::where('email', $email)->first();

        if ($user) {
            return ['id' => $user->id, 'created' => false, 'email' => $email];
        }

        try {
            $id = User::create([
                'name' => $customer['customer_name'],
                'email' => $email,
                'password' => Str::password(40),
            ])->id;
        } catch (UniqueConstraintViolationException) {
            // Two guests checked out with the same brand-new email at the same
            // moment and one lost the race for the unique index. The account
            // that just won is exactly the one this order belongs to, so adopt
            // it rather than failing the customer's order over it. Whoever lost
            // the race did not create the account, so no claim mail goes out for
            // them — the one who did will have mailed it already.
            return ['id' => User::where('email', $email)->firstOrFail()->id, 'created' => false, 'email' => $email];
        }

        return ['id' => $id, 'created' => true, 'email' => $email];
    }

    /**
     * Tells a guest that the account their order created can now be claimed, by
     * emailing the same verification code a forgotten password is reset with
     * (see PasswordResetService).
     *
     * Without it the order is attached to an account the customer has never
     * heard of and cannot sign into: the random password is never disclosed, so
     * /account/orders would be unreachable to them and the order history —
     * which is right there, one code away — would look like it had vanished.
     * Reusing the storefront's own reset flow means the customer needs nothing
     * but the address they ordered with, and nothing new has to be built or
     * maintained: the code goes only to that address, which is the only proof the
     * requester has of the account.
     *
     * Best-effort, like the order emails (see OrderEmailService): a mail
     * failure here is not a reason to lose a placed order, and the customer can
     * still ask for a code themselves from the storefront's forgot-password page
     * — the log line is what tells an admin it went missing.
     */
    private function sendAccountClaimEmail(string $email): void
    {
        $status = app(PasswordResetService::class)->requestCode($email, PasswordResetService::REASON_ORDER);

        if ($status === OtpService::FAILED) {
            Log::error('Failed to send the guest account claim email', [
                'email' => $email,
                'error' => 'the code could not be emailed',
            ]);

            return;
        }

        if ($status === OtpService::THROTTLED) {
            // A code for this address is already outstanding, sent moments ago.
            // That is either a repeat order from the same person or a second
            // guest on a shared address, and either way the code already in their
            // inbox is the one that works — reissuing would only invalidate it.
            Log::warning('Guest account claim email skipped — a code was already sent', [
                'email' => $email,
            ]);
        }
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
