<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends a text message to a Telegram chat through the Bot API (sendMessage).
 *
 * The bot token and chat id are TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID in .env
 * (config/services.php), edited at Admin → Settings → Telegram. They are
 * secrets, so they are kept out of the settings table, which the public API
 * and the settings export read from.
 *
 * Sending never throws: a notification channel being down must not fail the
 * request that triggered it (a visitor's contact form is already saved by the
 * time this runs). The result is a bool, and failures are logged without the
 * exception message — Laravel's HTTP errors carry the full request URL, and the
 * bot token is part of that URL.
 */
final class Telegram
{
    /** Telegram rejects a message longer than this. */
    private const MAX_LENGTH = 4096;

    /** Whether both the token and the chat id have been filled in. */
    public static function configured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    /**
     * Announces a new contact-form submission, if the Contacts → Notifications
     * "Send to Telegram" switch is on. The one place both ways a contact arrives
     * (the public API and the themes' Livewire contact form) go through, so the
     * switch means the same thing for both.
     *
     * @param  array<string, mixed>  $details  Label => value; empty values are left out.
     */
    public static function notifyContact(string $subject, array $details): void
    {
        if (! Setting::contactTelegramEnabled()) {
            return;
        }

        $lines = collect($details)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $label) => "{$label}: {$value}")
            ->implode("\n");

        self::send("New contact: {$subject}\n\n{$lines}");
    }

    /**
     * @param  ?string  $token  Override the saved token (the Settings screen's "Send test" uses what is typed, before saving).
     * @param  ?string  $chatId  Override the saved chat id.
     */
    public static function send(string $text, ?string $token = null, ?string $chatId = null): bool
    {
        $token = trim((string) ($token ?? config('services.telegram.bot_token')));
        $chatId = trim((string) ($chatId ?? config('services.telegram.chat_id')));

        if ($token === '' || $chatId === '') {
            return false;
        }

        try {
            return Http::asForm()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => Str::limit($text, self::MAX_LENGTH - 3, '...'),
                ])
                ->successful();
        } catch (Throwable $e) {
            Log::warning('Telegram message could not be sent ('.class_basename($e).').');

            return false;
        }
    }
}
