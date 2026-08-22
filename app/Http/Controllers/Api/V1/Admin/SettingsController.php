<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AdminActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $settings = Setting::query()
            ->when($request->query('group'), fn ($q, $group) => $q->where('group', $group))
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return response()->json([
            'data' => $settings->map(fn ($setting) => $this->formatSetting($setting)),
        ]);
    }

    /**
     * Bulk update, matching the Livewire Settings screen's own save() — every
     * key/value pair is written through Setting::set() (busts the per-key
     * cache; see Setting model). Only existing keys can be touched here, so
     * the API can't be used to seed arbitrary new settings rows.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array|min:1',
        ]);

        $keys = array_keys($validated['settings']);
        $unknown = array_diff($keys, Setting::whereIn('key', $keys)->pluck('key')->all());

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'settings' => ['Unknown setting key(s): '.implode(', ', $unknown)],
            ]);
        }

        foreach ($validated['settings'] as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        }

        AdminActivity::log('updated', 'Settings updated: '.implode(', ', $keys));

        return response()->json([
            'data' => Setting::whereIn('key', $keys)->get()->map(fn ($s) => $this->formatSetting($s)),
        ]);
    }

    private function formatSetting(Setting $setting): array
    {
        return [
            'key' => $setting->key,
            'value' => $setting->type === 'boolean' ? (bool) $setting->value : $setting->value,
            'type' => $setting->type,
            'group' => $setting->group,
            'is_public' => (bool) $setting->is_public,
        ];
    }
}
