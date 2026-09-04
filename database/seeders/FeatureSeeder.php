<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Setting;
use App\Support\Features as FeatureList;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Feature toggles used to live as individual `settings` rows (feature_{key}).
     * Existing values are carried over here so upgrading an already-seeded
     * database doesn't reset what an admin had already turned off.
     */
    public function run(): void
    {
        foreach (FeatureList::ALL as $key => $label) {
            Feature::updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'is_enabled' => (bool) Setting::get(FeatureList::settingKey($key), true),
                ]
            );
        }

        Setting::where('key', 'like', 'feature\_%')->delete();
    }
}
