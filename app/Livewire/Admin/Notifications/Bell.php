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

    public function render()
    {
        return view('livewire.admin.notifications.bell', [
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
            'notifications' => auth()->user()->notifications()->latest()->limit(8)->get(),
        ]);
    }
}
