<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Cash on Delivery is the only payment method actually processed end-to-end —
 * its "processing" is simply staying pending until an admin marks it paid on
 * delivery, which is how COD works in reality anyway. The gateways below
 * (PayPal, Stripe, bKash, SSLCommerz, Apple Pay) are structural placeholders:
 * selectable at checkout once enabled in Settings → Payments, and recorded on
 * the order/transaction, but no real SDK/webhook integration exists yet — an
 * order paid through one of them just stays payment_status=pending until that
 * gateway is actually wired up.
 */
class PaymentMethods
{
    public const COD = 'cod';

    private const GATEWAYS = [
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'bkash' => 'bKash',
        'sslcommerz' => 'SSLCommerz',
        'applepay' => 'Apple Pay',
    ];

    /**
     * @return array<string, string> method key => label
     */
    public static function available(): array
    {
        $methods = [self::COD => 'Cash on Delivery'];

        foreach (self::GATEWAYS as $key => $label) {
            if ((bool) Setting::get("{$key}_enabled", false)) {
                $methods[$key] = $label;
            }
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
