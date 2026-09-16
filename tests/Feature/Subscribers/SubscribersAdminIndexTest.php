<?php

use App\Livewire\Admin\Subscribers\Index as SubscribersIndex;
use App\Models\Subscriber;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
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
