<?php

use App\Livewire\Admin\Social\Index as SocialIndex;
use App\Models\SocialLink;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SocialLinkSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('seeder creates the default social link rows', function () {
    $this->artisan('db:seed', ['--class' => SocialLinkSeeder::class]);

    foreach (['facebook', 'twitter', 'instagram', 'youtube', 'linkedin', 'tiktok', 'whatsapp'] as $platform) {
        expect(SocialLink::where('platform', $platform)->exists())->toBeTrue();
    }
});

it('renders the social links screen', function () {
    $this->artisan('db:seed', ['--class' => SocialLinkSeeder::class]);

    Livewire::test(SocialIndex::class)
        ->assertStatus(200)
        ->assertSee('Facebook')
        ->assertSee('Instagram')
        ->assertSee('YouTube')
        ->assertSee('LinkedIn')
        ->assertSee('WhatsApp');
});

it('is reachable at its own admin route', function () {
    $this->artisan('db:seed', ['--class' => SocialLinkSeeder::class]);

    $this->get(route('admin.social'))->assertOk();
});

it('saves social link urls through the form', function () {
    $this->artisan('db:seed', ['--class' => SocialLinkSeeder::class]);

    Livewire::test(SocialIndex::class)
        ->set('links.0.url', 'https://facebook.com/codeware')
        ->call('save')
        ->assertHasNoErrors();

    expect(SocialLink::where('platform', 'facebook')->value('url'))->toBe('https://facebook.com/codeware');
});

it('exposes saved urls through SocialLink::url()', function () {
    $this->artisan('db:seed', ['--class' => SocialLinkSeeder::class]);

    SocialLink::where('platform', 'facebook')->update(['url' => 'https://facebook.com/codeware']);

    expect(SocialLink::url('facebook'))->toBe('https://facebook.com/codeware')
        ->and(SocialLink::url('twitter'))->toBeNull();
});

it('blocks staff from the social links screen', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.social'))->assertForbidden();
});
