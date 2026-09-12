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
                'key' => 'contact_phone',
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
                'key' => 'copyright_name',
                'value' => 'Codeware Limited',
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
                'value' => '#1e7bc4',
                'type' => 'color',
                'group' => 'colors',
                'is_public' => true,
            ],
            [
                'key' => 'secondary_color',
                'value' => '#7cc242',
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
            ['key' => 'seo_twitter_card',          'value' => 'summary_large_image', 'type' => 'select', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_twitter_site',          'value' => '', 'type' => 'string', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_twitter_title',         'value' => '', 'type' => 'string', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_twitter_description',   'value' => '', 'type' => 'textarea', 'group' => 'seo', 'is_public' => true],
            ['key' => 'seo_twitter_image',         'value' => '', 'type' => 'string', 'group' => 'seo', 'is_public' => true],

            // ── Localization ──
            ['key' => 'app_locale',               'value' => 'en', 'type' => 'string', 'group' => 'localization', 'is_public' => true],
            ['key' => 'timezone',                 'value' => 'UTC', 'type' => 'select', 'group' => 'localization', 'is_public' => true],
            ['key' => 'date_format',              'value' => 'd M Y, h:i A', 'type' => 'select', 'group' => 'localization', 'is_public' => true],

            // ── Frontend ──
            ['key' => 'site_theme',               'value' => 'default', 'type' => 'select', 'group' => 'frontend', 'is_public' => true],

            // ── Editor ──
            ['key' => 'puck_session_minutes',    'value' => '30', 'type' => 'integer', 'group' => 'editor', 'is_public' => false],

            // ── Newsletter ── whether a subscriber email blast fires when a new
            // product is created (Product::booted()).
            ['key' => 'notify_subscribers_on_new_product', 'value' => '1', 'type' => 'boolean', 'group' => 'newsletter', 'is_public' => false],

            // ── Custom Code ── raw HTML/JS injected into <head> / before </body> by the
            // frontend (e.g. Google Analytics, Facebook Pixel) — public since the
            // frontend needs the raw markup, not just a flag.
            ['key' => 'custom_head_code',         'value' => '', 'type' => 'textarea', 'group' => 'custom-code', 'is_public' => true],
            ['key' => 'custom_body_code',         'value' => '', 'type' => 'textarea', 'group' => 'custom-code', 'is_public' => true],

            // ── Tracking ── Google Pixel / Measurement ID, exposed via the public
            // settings API so the frontend can inject the gtag script itself.
            ['key' => 'google_pixel_id', 'value' => '', 'type' => 'string', 'group' => 'tracking', 'is_public' => true],

            // ── Floating button (admin panel) ──
            ['key' => 'floating_button_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'other', 'is_public' => false],
            ['key' => 'floating_button_action',  'value' => 'top', 'type' => 'string', 'group' => 'other', 'is_public' => false],
            ['key' => 'floating_button_link',    'value' => '', 'type' => 'string', 'group' => 'other', 'is_public' => false],

            // ── Table actions display (admin panel) ── how row action buttons
            // (Edit, Delete, ...) render on admin list tables — inline icon
            // buttons, or collapsed behind a single three-dot dropdown.
            ['key' => 'admin_actions_display', 'value' => 'inline', 'type' => 'select', 'group' => 'other', 'is_public' => false],

            // ── Watermark (admin panel) ── stamps every future Media Library
            // upload with this image at this position/opacity (see ImageWatermarker).
            ['key' => 'watermark_enabled',  'value' => '0', 'type' => 'boolean', 'group' => 'other', 'is_public' => false],
            ['key' => 'watermark_image',    'value' => '', 'type' => 'string', 'group' => 'other', 'is_public' => false],
            ['key' => 'watermark_position', 'value' => 'bottom-right', 'type' => 'select', 'group' => 'other', 'is_public' => false],
            ['key' => 'watermark_opacity',  'value' => '50', 'type' => 'integer', 'group' => 'other', 'is_public' => false],

            // ── Calculator widget (admin panel) ── floating draggable calculator
            // opened from the header icon or the Ctrl+Alt+C shortcut.
            ['key' => 'calculator_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'other', 'is_public' => false],

            // ── Sticky note widget (admin panel) ── floating draggable note
            // opened from the header icon or the Ctrl+Alt+N shortcut.
            ['key' => 'sticky_note_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'other', 'is_public' => false],

            // ── reCAPTCHA (login page) ── shown/verified only when this is on
            // AND the Site/Secret keys below are set — see Settings → Env.
            ['key' => 'recaptcha_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'other', 'is_public' => false],

            // ── Shop status ── whether customers can currently place orders
            // (see OrderController::store()). Public since the frontend needs
            // it to show a "shop closed" state. Own group, excluded from the
            // generic Settings-page render loop (see Settings\Index::render())
            // since it's controlled only via the header toggle, not a form field.
            ['key' => 'shop_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'shop', 'is_public' => true],

            // ── Shop toggle (admin panel) ── shows/hides the Shop On/Off button
            // in the admin header. Off by default so the header stays
            // uncluttered until an admin opts in from Settings → Other.
            ['key' => 'shop_toggle_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'other', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
