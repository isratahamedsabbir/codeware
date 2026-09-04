<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    /**
     * Social links used to live as individual `settings` rows (group=social).
     * Existing values are carried over here so upgrading an already-seeded
     * database doesn't lose whatever an admin had already entered.
     */
    public function run(): void
    {
        $platforms = [
            ['platform' => 'facebook', 'label' => 'Facebook', 'settingKey' => 'facebook_url'],
            ['platform' => 'twitter', 'label' => 'Twitter / X', 'settingKey' => 'twitter_url'],
            ['platform' => 'instagram', 'label' => 'Instagram', 'settingKey' => 'instagram_url'],
            ['platform' => 'youtube', 'label' => 'YouTube', 'settingKey' => 'youtube_url'],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'settingKey' => 'linkedin_url'],
            ['platform' => 'tiktok', 'label' => 'TikTok', 'settingKey' => 'tiktok_url'],
            ['platform' => 'whatsapp', 'label' => 'WhatsApp', 'settingKey' => 'whatsapp_number'],
        ];

        foreach ($platforms as $index => $platform) {
            SocialLink::updateOrCreate(
                ['platform' => $platform['platform']],
                [
                    'label' => $platform['label'],
                    'url' => Setting::get($platform['settingKey'], ''),
                    'sort_order' => $index,
                ]
            );
        }

        Setting::where('group', 'social')->delete();
    }
}
