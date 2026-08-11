<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function public(): JsonResponse
    {
        $settings = Setting::where('is_public', true)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => $s->value]);

        return response()->json(['data' => $settings]);
    }
}
