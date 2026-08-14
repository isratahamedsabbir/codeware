<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic admin-panel notification — title/message/link, shown in the admin
 * header bell. Replaces the old bespoke AdminNotification model/table with
 * Laravel's native notifications system (User already uses Notifiable).
 */
class AdminAlert extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public ?string $message = null,
        public ?string $link = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }
}
