<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function public(): JsonResponse
    {
        return response()->json(['data' => Setting::publicMap()]);
    }
}
