<?php

namespace App\Livewire\Frontend;

use App\Events\MessageSent;
use App\Mail\ChatOtpMail;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Public-facing chat bubble (bottom-right, all frontend themes). A visitor gives
 * their name + email, gets a 6-digit code by email, and once verified a real
 * `users` account is created for them (is_admin stays false) so the conversation
 * rides on the exact same Conversation/ChatMessage/MessageSent machinery the
 * internal admin Chat module already uses — the admin sees replies instantly in
 * their own Chat inbox, no separate guest-chat system needed.
 *
 * Real-time is one-directional by design: the admin side already has a live Echo
 * subscription, but the widget itself just polls (wire:poll) for new messages —
 * that avoids needing to authorize a private broadcast channel for an anonymous
 * visitor, which the app's existing channel-auth (routes/channels.php) has no
 * story for.
 */
class ChatWidget extends Component
{
    public bool $isOpen = false;

    public string $step = 'form'; // form | otp | chat

    public string $name = '';

    public string $email = '';

    public string $otp = '';

    public string $messageBody = '';

    public ?int $conversationId = null;

    public ?int $guestUserId = null;

    public ?string $adminName = null;

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function requestOtp(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if (Cache::has($this->cooldownKey())) {
            $this->addError('email', __('Please wait a moment before requesting another code.'));

            return;
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->otpKey(), $code, now()->addMinutes(10));
        Cache::put($this->cooldownKey(), true, now()->addSeconds(45));

        Mail::to($this->email)->send(new ChatOtpMail($code, $this->name));

        $this->step = 'otp';
    }

    public function resendOtp(): void
    {
        $this->otp = '';
        $this->requestOtp();
    }

    public function changeEmail(): void
    {
        $this->otp = '';
        $this->resetErrorBag();
        $this->step = 'form';
    }

    public function verifyOtp(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $expected = Cache::get($this->otpKey());

        if (! $expected || ! hash_equals($expected, $this->otp)) {
            $this->addError('otp', __('That code is incorrect or has expired.'));

            return;
        }

        $admin = User::where('is_admin', true)->oldest()->first();

        if (! $admin) {
            $this->addError('otp', __('Chat support is not available right now.'));

            return;
        }

        Cache::forget($this->otpKey());

        $guest = User::firstOrCreate(
            ['email' => $this->email],
            ['name' => $this->name, 'password' => Str::random(40)],
        );

        if (! $guest->email_verified_at) {
            $guest->forceFill(['email_verified_at' => now()])->save();
        }

        $conversation = Conversation::between($guest, $admin);

        $this->conversationId = $conversation->id;
        $this->guestUserId = $guest->id;
        $this->adminName = $admin->name;
        $this->step = 'chat';

        $this->dispatch('chat-widget-verified', token: encrypt([
            'conversation_id' => $conversation->id,
            'user_id' => $guest->id,
        ]));
    }

    public function resume(string $token): void
    {
        try {
            $data = decrypt($token);
        } catch (Throwable) {
            return;
        }

        $conversation = Conversation::find($data['conversation_id'] ?? null);
        $guest = User::find($data['user_id'] ?? null);

        if (! $conversation || ! $guest || $guest->is_admin || ! $conversation->isParticipant($guest)) {
            return;
        }

        $this->conversationId = $conversation->id;
        $this->guestUserId = $guest->id;
        $this->adminName = $conversation->otherUser($guest)->name;
        $this->step = 'chat';
    }

    public function sendMessage(): void
    {
        $this->validate([
            'messageBody' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = Conversation::find($this->conversationId);
        $guest = User::find($this->guestUserId);

        if (! $conversation || ! $guest || ! $conversation->isParticipant($guest)) {
            return;
        }

        $message = $conversation->messages()->create([
            'sender_id' => $guest->id,
            'body' => trim($this->messageBody),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $this->messageBody = '';

        broadcast(new MessageSent($message));
    }

    #[Computed]
    public function messages(): Collection
    {
        if (! $this->conversationId) {
            return collect();
        }

        return ChatMessage::where('conversation_id', $this->conversationId)
            ->orderBy('created_at')
            ->get();
    }

    private function otpKey(): string
    {
        return 'chat-widget-otp:'.strtolower($this->email);
    }

    private function cooldownKey(): string
    {
        return 'chat-widget-otp-cooldown:'.strtolower($this->email);
    }

    public function render()
    {
        return view('livewire.frontend.chat-widget');
    }
}
