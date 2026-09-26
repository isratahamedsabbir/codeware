<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * Every public setting the frontend needs, grouped the same way the admin
     * panel presents them (General/Images/Pagination/Localization/Currency on
     * the Settings screen, Theme colors, Constant, plus the separate Global
     * SEO and Social Links admin sections) rather than one flat key=>value map.
     *
     * The per-language settings (the global SEO copy) come back as the string
     * for `?locale=`, not as the stored `{code: value}` map: a consumer
     * assembling a <title> has one language to render, and the map is the admin
     * form's shape, not the API's.
     */
    public function public(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $data = Cache::remember('settings:public:'.$locale.':v'.Setting::cacheVersion(), null, function () use ($locale) {
            // Response key => DB group, one key per published group. 'theme' is
            // the public name for the admin's 'colors' group; 'custom_code' for
            // 'custom-code'.
            $map = [
                'general' => 'general',
                'images' => 'images',
                'pagination' => 'pagination',
                'localization' => 'localization',
                'currency' => 'currency',
                'theme' => 'colors',
                'frontend' => 'frontend',
                'tracking' => 'tracking',
                'shop' => 'shop',
                'custom_code' => 'custom-code',
                'seo' => 'seo',
            ];

            // Single query for every public setting, then grouped in PHP —
            // one SELECT instead of one per group.
            $grouped = Setting::where('is_public', true)
                ->get(['key', 'value', 'group'])
                ->groupBy('group');

            $data = collect($map)->mapWithKeys(fn (string $group, string $key) => [
                $key => $grouped->get($group, collect())->pluck('value', 'key')->all(),
            ])->all();

            $data['seo'] = collect($data['seo'])
                ->map(fn ($value, string $key) => Setting::isTranslatable($key)
                    ? Setting::translate($value, $locale)
                    : $value)
                ->all();

            $data['seo']['seo_canonical_urls'] = json_decode($data['seo']['seo_canonical_urls'] ?? '[]', true) ?: [];
            $data['constant'] = $this->constants();
            $data['social_links'] = SocialLink::urlsCached();

            return $data;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * The same `?locale=` contract every other public endpoint takes, defaulting
     * to the site's configured locale.
     */
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return is_string($locale) && Locale::isSupported($locale) ? $locale : Locale::default();
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
