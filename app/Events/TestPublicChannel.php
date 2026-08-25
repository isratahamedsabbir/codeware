<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestPublicChannel implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;

    public $msg;

    public function __construct(array $data)
    {
        $this->user_id = $data['user_id'];
        $this->msg = $data['msg'] ?? 'Hello from public channel';
    }

    public function broadcastOn(): Channel
    {
        return new Channel('test-public-channel');
    }
}
