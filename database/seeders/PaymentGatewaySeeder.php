<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Payment gateway credentials used to live as individual `settings` rows
     * (group=payments, one key per field). Existing values are carried over
     * here so upgrading an already-seeded database doesn't lose whatever an
     * admin had already entered.
     */
    public function run(): void
    {
        $gateways = [
            [
                'code' => 'paypal',
                'name' => 'PayPal',
                'mode' => Setting::get('paypal_mode', 'sandbox'),
                'credentials' => [
                    'client_id' => Setting::get('paypal_client_id', ''),
                    'client_secret' => Setting::get('paypal_client_secret', ''),
                ],
            ],
            [
                'code' => 'stripe',
                'name' => 'Stripe',
                'mode' => Setting::get('stripe_mode', 'test'),
                'credentials' => [
                    'publishable_key' => Setting::get('stripe_publishable_key', ''),
                    'secret_key' => Setting::get('stripe_secret_key', ''),
                    'webhook_secret' => Setting::get('stripe_webhook_secret', ''),
                ],
            ],
            [
                'code' => 'bkash',
                'name' => 'bKash',
                'mode' => Setting::get('bkash_mode', 'sandbox'),
                'credentials' => [
                    'username' => Setting::get('bkash_username', ''),
                    'password' => Setting::get('bkash_password', ''),
                    'app_key' => Setting::get('bkash_app_key', ''),
                    'app_secret' => Setting::get('bkash_app_secret', ''),
                ],
            ],
            [
                'code' => 'sslcommerz',
                'name' => 'SSLCommerz',
                'mode' => Setting::get('sslcommerz_mode', 'sandbox'),
                'credentials' => [
                    'store_id' => Setting::get('sslcommerz_store_id', ''),
                    'store_password' => Setting::get('sslcommerz_store_password', ''),
                ],
            ],
            [
                'code' => 'applepay',
                'name' => 'Apple Pay',
                'mode' => null,
                'credentials' => [
                    'merchant_id' => Setting::get('applepay_merchant_id', ''),
                    'merchant_name' => Setting::get('applepay_merchant_name', ''),
                    'domain' => Setting::get('applepay_domain', ''),
                ],
            ],
        ];

        foreach ($gateways as $index => $gateway) {
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                [
                    'name' => $gateway['name'],
                    'is_enabled' => (bool) Setting::get("{$gateway['code']}_enabled", false),
                    'mode' => $gateway['mode'],
                    'credentials' => $gateway['credentials'],
                    'sort_order' => $index,
                ]
            );
        }

        Setting::where('group', 'payments')->delete();
    }
}
