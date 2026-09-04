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
                'value' => 'Codeware',
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
                'key' => 'contact_email',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'contact_address',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'order_email',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'pagination_per_page',
                'value' => '10',
                'type' => 'integer',
                'group' => 'pagination',
                'is_public' => true,
            ],
            [
                'key' => 'site_icon',
                'value' => '',
                'type' => 'string',
                'group' => 'images',
                'is_public' => true,
            ],
            [
                'key' => 'site_icon_white',
                'value' => '',
                'type' => 'string',
                'group' => 'images',
                'is_public' => true,
            ],
            [
                'key' => 'favicon',
                'value' => '',
                'type' => 'string',
                'group' => 'images',
                'is_public' => true,
            ],
            [
                'key' => 'loader',
                'value' => '',
                'type' => 'string',
                'group' => 'images',
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

            // ── Localization ──
            ['key' => 'app_locale',               'value' => 'en', 'type' => 'string', 'group' => 'localization', 'is_public' => true],
            ['key' => 'timezone',                 'value' => 'UTC', 'type' => 'select', 'group' => 'localization', 'is_public' => true],

            // ── Frontend ──
            ['key' => 'site_theme',               'value' => 'default', 'type' => 'select', 'group' => 'frontend', 'is_public' => true],

            // ── Floating button (admin panel) ──
            ['key' => 'floating_button_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'other', 'is_public' => false],
            ['key' => 'floating_button_action',  'value' => 'top', 'type' => 'string', 'group' => 'other', 'is_public' => false],
            ['key' => 'floating_button_link',    'value' => '', 'type' => 'string', 'group' => 'other', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
