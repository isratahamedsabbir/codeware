<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Debounces the "new contact" admin notification to one per minute. The first
 * contact in a window alerts immediately; the rest are counted and sent as a
 * single summary by `contacts:flush-alerts` (scheduled every minute), so a
 * flood of submissions can't fill every admin's inbox.
 */
class ContactAlerts
{
    private const WINDOW = 60;

    public static function record(Contact $contact): void
    {
        Cache::add('contacts:alert-pending', 0, 3600);
        Cache::increment('contacts:alert-pending');
        Cache::put('contacts:alert-last', "{$contact->full_name}: {$contact->subject}", 3600);

        if (Cache::add('contacts:alert-window', true, self::WINDOW)) {
            self::flush();
        }
    }

    /** Sends the summary if anything is pending and the window is free. */
    public static function flushDue(): void
    {
        if ((int) Cache::get('contacts:alert-pending', 0) > 0 && Cache::add('contacts:alert-window', true, self::WINDOW)) {
            self::flush();
        }
    }

    private static function flush(): void
    {
        $count = (int) Cache::pull('contacts:alert-pending', 0);
        $last = (string) Cache::get('contacts:alert-last', '');

        if ($count < 1) {
            return;
        }

        Notification::send(
            User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get(),
            new AdminAlert(
                $count === 1 ? 'New contact message' : "{$count} new contact messages",
                $count === 1 ? $last : "Latest — {$last}",
                route('admin.contacts'),
            ),
        );
    }
}
