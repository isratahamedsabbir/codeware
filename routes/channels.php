<?php

use Illuminate\Support\Facades\Broadcast;

// Chat messages broadcast to the recipient's own private channel below — no
// separate per-conversation channel needed for 1-on-1 chat.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('test-private-channel.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
