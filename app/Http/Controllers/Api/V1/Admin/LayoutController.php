<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LayoutController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:header,footer',
            'content' => 'required',
        ]);

        $key = $validated['type'] === 'header' ? 'header_content' : 'footer_content';
        $content = is_array($validated['content']) ? json_encode($validated['content']) : $validated['content'];
        Setting::set($key, $content);

        return response()->json(['data' => ['key' => $key, 'updated' => true]]);
    }
}
