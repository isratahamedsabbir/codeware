<?php

namespace App\Livewire\Admin\Notifications;

use Livewire\Component;

class Bell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->adminNotifications()->unread()->update(['read_at' => now()]);

        $this->dispatch('notify', message: 'All notifications marked as read');
    }

    public function markAsRead(int $id): void
    {
        auth()->user()->adminNotifications()->whereKey($id)->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.admin.notifications.bell', [
            'unreadCount' => auth()->user()->adminNotifications()->unread()->count(),
            'notifications' => auth()->user()->adminNotifications()->latest()->limit(8)->get(),
        ]);
    }
}
