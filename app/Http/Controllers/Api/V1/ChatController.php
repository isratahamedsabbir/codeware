<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Mail\ChatOtpMail;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Public guest-chat API: the REST equivalent of the Frontend\ChatWidget Livewire
 * component (name/email -> OTP -> chat), for clients that can't run Livewire
 * (e.g. the Next.js frontend). It rides the same Conversation/ChatMessage/
 * MessageSent machinery and the same cache-backed OTP keys, so a code requested
 * from one surface can be verified from the other.
 *
 * Guests aren't issued a Sanctum token — verifyOtp returns an encrypted token
 * (conversation_id + user_id) the client must echo back as a Bearer token on
 * /chat/messages, the same shape ChatWidget::resume() already accepts.
 */
class ChatController extends Controller
{
    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower($validated['email']);

        if (Cache::has($this->cooldownKey($email))) {
            throw ValidationException::withMessages([
                'email' => 'Please wait a moment before requesting another code.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->otpKey($email), $code, now()->addMinutes(10));
        Cache::put($this->cooldownKey($email), true, now()->addSeconds(45));

        Mail::to($validated['email'])->send(new ChatOtpMail($code, $validated['name']));

        return response()->json([
            'message' => 'A verification code has been sent to your email.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = strtolower($validated['email']);
        $expected = Cache::get($this->otpKey($email));

        if (! $expected || ! hash_equals($expected, $validated['otp'])) {
            throw ValidationException::withMessages([
                'otp' => 'That code is incorrect or has expired.',
            ]);
        }

        $admin = User::where('is_admin', true)->oldest()->first();

        if (! $admin) {
            return response()->json([
                'message' => 'Chat support is not available right now.',
            ], 503);
        }

        Cache::forget($this->otpKey($email));

        $guest = User::firstOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name'], 'password' => Str::random(40)],
        );

        if (! $guest->email_verified_at) {
            $guest->forceFill(['email_verified_at' => now()])->save();
        }

        $conversation = Conversation::between($guest, $admin);

        return response()->json([
            'data' => [
                'token' => encrypt(['conversation_id' => $conversation->id, 'user_id' => $guest->id]),
                'conversation_id' => $conversation->id,
                'admin_name' => $admin->name,
                'guest' => ['id' => $guest->id, 'name' => $guest->name, 'email' => $guest->email],
                'messages' => $this->formatMessages($conversation),
            ],
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        [$conversation] = $this->resolveGuestConversation($request);

        $sinceId = $request->integer('since_id') ?: null;

        $query = $conversation->messages()->orderBy('created_at');

        if ($sinceId) {
            $query->where('id', '>', $sinceId);
        }

        return response()->json([
            'data' => $this->formatMessages($conversation, $query->get()),
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        [$conversation, $guest] = $this->resolveGuestConversation($request);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $guest->id,
            'body' => trim($validated['body']),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        broadcast(new MessageSent($message));

        return response()->json([
            'data' => $this->formatMessage($message->fresh()),
        ], 201);
    }

    /**
     * @return array{0: Conversation, 1: User}
     */
    private function resolveGuestConversation(Request $request): array
    {
        $token = $request->bearerToken();

        try {
            $data = $token ? decrypt($token) : null;
        } catch (Throwable) {
            $data = null;
        }

        $conversation = Conversation::find($data['conversation_id'] ?? null);
        $guest = User::find($data['user_id'] ?? null);

        if (! $token || ! $data || ! $conversation || ! $guest || $guest->is_admin || ! $conversation->isParticipant($guest)) {
            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }

        return [$conversation, $guest];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatMessages(Conversation $conversation, ?Collection $messages = null): array
    {
        $messages ??= $conversation->messages()->orderBy('created_at')->get();

        return $messages->map(fn (ChatMessage $message) => $this->formatMessage($message))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    private function otpKey(string $email): string
    {
        return 'chat-widget-otp:'.$email;
    }

    private function cooldownKey(string $email): string
    {
        return 'chat-widget-otp-cooldown:'.$email;
    }
}
