<?php

namespace App\Support;

use App\Models\PaymentGateway;

/**
 * Cash on Delivery is the only payment method actually processed end-to-end —
 * its "processing" is simply staying pending until an admin marks it paid on
 * delivery, which is how COD works in reality anyway. The gateways below
 * (PayPal, Stripe, bKash, SSLCommerz, Apple Pay) are structural placeholders:
 * selectable at checkout once enabled in Settings → Payment Gateways, and
 * recorded on the order/transaction, but no real SDK/webhook integration
 * exists yet — an order paid through one of them just stays
 * payment_status=pending until that gateway is actually wired up.
 */
class PaymentMethods
{
    public const COD = 'cod';

    /**
     * @return array<string, string> method key => label
     */
    public static function available(): array
    {
        $methods = [self::COD => 'Cash on Delivery'];

        foreach (PaymentGateway::enabled()->orderBy('sort_order')->get() as $gateway) {
            $methods[$gateway->code] = $gateway->name;
        }

        return $methods;
    }

    public static function isAvailable(string $method): bool
    {
        return array_key_exists($method, self::available());
    }

    public static function label(string $method): string
    {
        return self::available()[$method] ?? ucfirst($method);
    }
}
