<?php

namespace App\Livewire\Admin\Notifications;

use Livewire\Component;

class Bell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        $this->dispatch('notify', message: 'All notifications marked as read');
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->first()?->markAsRead();
    }

    public function delete(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->delete();
    }

    /** Deletes every notification of the signed-in admin, read or not. */
    public function clearAll(): void
    {
        auth()->user()->notifications()->delete();

        $this->dispatch('notify', message: 'All notifications cleared');
    }

    public function render()
    {
        return view('livewire.admin.notifications.bell', [
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
            'notifications' => auth()->user()->notifications()->latest()->limit(8)->get(),
        ]);
    }
}
