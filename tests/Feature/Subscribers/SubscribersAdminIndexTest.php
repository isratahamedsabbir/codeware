<?php

use App\Livewire\Admin\Subscribers\Index as SubscribersIndex;
use App\Models\Subscriber;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('opens and closes the view details modal for a subscriber', function () {
    $subscriber = Subscriber::factory()->create(['email' => 'jane@example.com']);

    Livewire::test(SubscribersIndex::class)
        ->call('viewDetails', $subscriber->id)
        ->assertSet('viewingId', $subscriber->id)
        ->assertSee('jane@example.com')
        ->call('closeDetails')
        ->assertSet('viewingId', null);
});
