<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class LayoutController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => [
                'header' => Setting::get('header_content'),
                'footer' => Setting::get('footer_content'),
            ],
        ]);
    }
}
