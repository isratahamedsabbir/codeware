<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'Agrosal',
                'type' => 'string',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'site_description',
                'value' => '',
                'type' => 'textarea',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'site_icon',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'favicon',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'contact_email',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'primary_color',
                'value' => '#2563eb',
                'type' => 'color',
                'group' => 'colors',
                'is_public' => true,
            ],
            [
                'key' => 'secondary_color',
                'value' => '#059669',
                'type' => 'color',
                'group' => 'colors',
                'is_public' => true,
            ],
            [
                'key' => 'tertiary_color',
                'value' => '#d97706',
                'type' => 'color',
                'group' => 'colors',
                'is_public' => true,
            ],
            [
                'key' => 'header_content',
                'value' => json_encode(['root' => ['props' => []], 'content' => [], 'zones' => []]),
                'type' => 'json',
                'group' => 'layout',
                'is_public' => false,
            ],
            [
                'key' => 'footer_content',
                'value' => json_encode(['root' => ['props' => []], 'content' => [], 'zones' => []]),
                'type' => 'json',
                'group' => 'layout',
                'is_public' => false,
            ],

            // ── Payment credentials ──
            ['key' => 'paypal_enabled',           'value' => '0', 'type' => 'boolean', 'group' => 'payments', 'is_public' => false],
            ['key' => 'paypal_mode',              'value' => 'sandbox', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'paypal_client_id',         'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'paypal_client_secret',     'value' => '', 'type' => 'password', 'group' => 'payments', 'is_public' => false],

            ['key' => 'stripe_enabled',           'value' => '0', 'type' => 'boolean', 'group' => 'payments', 'is_public' => false],
            ['key' => 'stripe_mode',              'value' => 'test', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'stripe_publishable_key',   'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'stripe_secret_key',        'value' => '', 'type' => 'password', 'group' => 'payments', 'is_public' => false],
            ['key' => 'stripe_webhook_secret',    'value' => '', 'type' => 'password', 'group' => 'payments', 'is_public' => false],

            ['key' => 'bkash_enabled',            'value' => '0', 'type' => 'boolean', 'group' => 'payments', 'is_public' => false],
            ['key' => 'bkash_mode',               'value' => 'sandbox', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'bkash_username',           'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'bkash_password',           'value' => '', 'type' => 'password', 'group' => 'payments', 'is_public' => false],
            ['key' => 'bkash_app_key',            'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'bkash_app_secret',         'value' => '', 'type' => 'password', 'group' => 'payments', 'is_public' => false],

            ['key' => 'sslcommerz_enabled',       'value' => '0', 'type' => 'boolean', 'group' => 'payments', 'is_public' => false],
            ['key' => 'sslcommerz_mode',          'value' => 'sandbox', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'sslcommerz_store_id',      'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'sslcommerz_store_password', 'value' => '', 'type' => 'password', 'group' => 'payments', 'is_public' => false],

            ['key' => 'applepay_enabled',         'value' => '0', 'type' => 'boolean', 'group' => 'payments', 'is_public' => false],
            ['key' => 'applepay_merchant_id',     'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'applepay_merchant_name',   'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
            ['key' => 'applepay_domain',          'value' => '', 'type' => 'string', 'group' => 'payments', 'is_public' => false],

            // ── Currency ──
            ['key' => 'currency_code',          'value' => 'BDT', 'type' => 'string', 'group' => 'currency', 'is_public' => true],
            ['key' => 'currency_symbol',        'value' => '৳', 'type' => 'string', 'group' => 'currency', 'is_public' => true],
            ['key' => 'currency_position',      'value' => 'left', 'type' => 'string', 'group' => 'currency', 'is_public' => true],
            ['key' => 'decimal_places',         'value' => '2', 'type' => 'number', 'group' => 'currency', 'is_public' => true],

            // ── SEO ──
            ['key' => 'seo_meta_title',           'value' => '', 'type' => 'string', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_meta_description',     'value' => '', 'type' => 'textarea', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_og_title',             'value' => '', 'type' => 'string', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_og_description',       'value' => '', 'type' => 'textarea', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_og_image',             'value' => '', 'type' => 'string', 'group' => 'seo', 'is_public' => true],

            // ── Theme ──
            ['key' => 'theme_mode',               'value' => 'light', 'type' => 'string', 'group' => 'theme', 'is_public' => false],
            ['key' => 'theme_name',               'value' => 'Default', 'type' => 'string', 'group' => 'theme', 'is_public' => false],
            ['key' => 'theme_accent',             'value' => '#1e7bc4', 'type' => 'color', 'group' => 'theme', 'is_public' => false],

            // ── Social links ──
            ['key' => 'facebook_url',        'value' => '', 'type' => 'string', 'group' => 'social', 'is_public' => true],
            ['key' => 'twitter_url',         'value' => '', 'type' => 'string', 'group' => 'social', 'is_public' => true],
            ['key' => 'instagram_url',       'value' => '', 'type' => 'string', 'group' => 'social', 'is_public' => true],
            ['key' => 'youtube_url',         'value' => '', 'type' => 'string', 'group' => 'social', 'is_public' => true],
            ['key' => 'linkedin_url',        'value' => '', 'type' => 'string', 'group' => 'social', 'is_public' => true],
            ['key' => 'tiktok_url',          'value' => '', 'type' => 'string', 'group' => 'social', 'is_public' => true],
            ['key' => 'whatsapp_number',     'value' => '', 'type' => 'string', 'group' => 'social', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
