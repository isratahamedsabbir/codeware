<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public ChatMessage $message)
    {
        $this->message->loadMissing(['sender', 'conversation']);
    }

    /**
     * Broadcast only to the recipient's own private channel (the same one Laravel
     * uses for notification broadcasting) — no separate per-conversation channel
     * is needed for 1-on-1 chat.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $recipient = $this->message->conversation->otherUser($this->message->sender);

        return [
            new PrivateChannel('App.Models.User.'.$recipient->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
