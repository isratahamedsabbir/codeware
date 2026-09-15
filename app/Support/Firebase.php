<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Throwable;

/**
 * Push notifications via Firebase Cloud Messaging. Reads the service-account
 * JSON configured at Settings → Env → Firebase (see Admin\Settings\Index's
 * envFields() and firebaseCredentialsExist()) off the private storage disk.
 * Every send is a best-effort no-op on failure — a missing/invalid key or an
 * unreachable FCM API should never break whatever feature tried to notify
 * a user, so failures are logged rather than thrown.
 */
class Firebase
{
    /**
     * Send to every FCM device token the given user has registered (see
     * FirebaseTokenController — POST /api/v1/firebase/tokens).
     */
    public static function sendToUser(User $user, string $title, string $body, ?string $imageUrl = null): void
    {
        foreach ($user->firebaseTokens as $firebaseToken) {
            self::sendToToken($firebaseToken->token, $title, $body, $imageUrl);
        }
    }

    /**
     * Send to one raw FCM device token directly.
     */
    public static function sendToToken(string $token, string $title, string $body, ?string $imageUrl = null): void
    {
        $credentialsPath = config('services.firebase.credentials_path');

        if (! $credentialsPath) {
            return;
        }

        try {
            $messaging = (new Factory)
                ->withServiceAccount(Storage::disk('local')->path($credentialsPath))
                ->createMessaging();

            $notification = FirebaseNotification::create($title, Str::limit($body, 100), $imageUrl);
            $message = CloudMessage::new()->withToken($token)->withNotification($notification);

            $messaging->send($message);
        } catch (Throwable $e) {
            Log::warning('Firebase push notification failed: '.$e->getMessage());
        }
    }
}
