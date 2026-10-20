<?php

use App\Livewire\Admin\Notifications\Bell;
use App\Models\User;
use App\Notifications\AdminAlert;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

it('clears all of the admin\'s notifications at once, and only theirs', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    foreach ([$admin, $other] as $user) {
        $user->notifications()->createMany(collect(range(1, 3))->map(fn () => [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => AdminAlert::class,
            'data' => ['title' => 'Hello'],
        ])->all());
    }

    Livewire::actingAs($admin)->test(Bell::class)->call('clearAll');

    expect($admin->notifications()->count())->toBe(0)
        ->and($other->notifications()->count())->toBe(3);
});
