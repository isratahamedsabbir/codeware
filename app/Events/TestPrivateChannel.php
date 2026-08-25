<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestPrivateChannel implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;

    public $msg;

    public function __construct(array $data)
    {
        $this->user_id = $data['user_id'];
        $this->msg = $data['msg'] ?? 'Hello from private channel';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('test-private-channel.'.$this->user_id),
        ];
    }
}
