<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /**
     * Every public setting the frontend needs, grouped the same way the admin
     * panel presents them (General/Images/Pagination/Localization/Currency on
     * the Settings screen, Theme colors, Constant, plus the separate Global
     * SEO and Social Links admin sections) rather than one flat key=>value map.
     */
    public function public(): JsonResponse
    {
        $seo = $this->group('seo');
        $seo['seo_canonical_urls'] = json_decode($seo['seo_canonical_urls'] ?? '[]', true) ?: [];

        return response()->json([
            'data' => [
                'general' => $this->group('general'),
                'images' => $this->group('images'),
                'pagination' => $this->group('pagination'),
                'localization' => $this->group('localization'),
                'currency' => $this->group('currency'),
                'theme' => $this->group('colors'),
                'tracking' => $this->group('tracking'),
                'shop' => $this->group('shop'),
                'constant' => $this->constants(),
                'seo' => $seo,
                'social_links' => SocialLink::urlsCached(),
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function group(string $group): array
    {
        return Setting::where('group', $group)->where('is_public', true)->pluck('value', 'key')->all();
    }

    /**
     * Freeform key/value pairs from the Constant settings tab, decoded from
     * their [{key,type,value}] storage shape into a flat key => value map —
     * see setting_constant() in app/Support/helpers.php for the same decode.
     *
     * @return array<string, string>
     */
    private function constants(): array
    {
        $constants = json_decode(Setting::get('constants', '[]') ?: '[]', true) ?: [];

        return collect($constants)->pluck('value', 'key')->all();
    }
}
