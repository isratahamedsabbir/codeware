<?php

namespace App\Http\Controllers;

use App\Events\TestPrivateChannel;
use App\Events\TestPublicChannel;
use App\Services\EmailTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function testEmail(Request $request, EmailTemplateService $emailTemplateService): JsonResponse
    {
        $sent = $emailTemplateService->send('test_email', setting('support_email'), [
            'name' => 'israt ahamed sabbir',
        ]);

        if ($sent === false) {
            return response()->json([
                'message' => 'Template not found/inactive or recipient is invalid.',
            ], 422);
        }

        return response()->json([
            'message' => 'Successfully sent test email using template.',
        ]);
    }

    public function testPrivateChannel()
    {
        broadcast(new TestPrivateChannel(['user_id' => auth()->id() ?? 1, 'msg' => 'hi i am sabbir.']));
        return response()->json([
            'message' => 'Successfully broadcasted to private channel.',
        ]);
    }

    public function testPublicChannel()
    {
        broadcast(new TestPublicChannel(['user_id' => 1, 'msg' => 'hi i am sabbir.']));
        return response()->json([
            'message' => 'Successfully broadcasted to public channel.',
        ]);
    }
}
