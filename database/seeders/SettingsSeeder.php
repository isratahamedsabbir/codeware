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
                'key'       => 'site_name',
                'value'     => 'Agrosal',
                'type'      => 'string',
                'group'     => 'general',
                'is_public' => true,
            ],
            [
                'key'       => 'site_description',
                'value'     => '',
                'type'      => 'string',
                'group'     => 'general',
                'is_public' => true,
            ],
            [
                'key'       => 'contact_email',
                'value'     => '',
                'type'      => 'string',
                'group'     => 'general',
                'is_public' => true,
            ],
            [
                'key'       => 'primary_color',
                'value'     => '#2563eb',
                'type'      => 'color',
                'group'     => 'colors',
                'is_public' => true,
            ],
            [
                'key'       => 'secondary_color',
                'value'     => '#059669',
                'type'      => 'color',
                'group'     => 'colors',
                'is_public' => true,
            ],
            [
                'key'       => 'tertiary_color',
                'value'     => '#d97706',
                'type'      => 'color',
                'group'     => 'colors',
                'is_public' => true,
            ],
            [
                'key'       => 'header_content',
                'value'     => json_encode(['root' => ['props' => []], 'content' => [], 'zones' => []]),
                'type'      => 'json',
                'group'     => 'layout',
                'is_public' => false,
            ],
            [
                'key'       => 'footer_content',
                'value'     => json_encode(['root' => ['props' => []], 'content' => [], 'zones' => []]),
                'type'      => 'json',
                'group'     => 'layout',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
