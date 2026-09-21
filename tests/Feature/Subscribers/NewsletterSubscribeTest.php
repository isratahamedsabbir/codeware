<?php

use App\Livewire\Frontend\NewsletterSubscribe;
use App\Models\Language;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('subscribes a new email from the footer box', function () {
    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'john@example.com')
        ->call('subscribe')
        ->assertSet('done', true)
        ->assertSet('already', false)
        ->assertSet('email', '');

    $this->assertDatabaseHas('subscribers', [
        'email' => 'john@example.com',
        'status' => 'subscribed',
    ]);
});

it('tells an already subscribed email it is already on the list', function () {
    Subscriber::factory()->create(['email' => 'john@example.com', 'status' => 'subscribed']);

    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'john@example.com')
        ->call('subscribe')
        ->assertSet('done', true)
        ->assertSet('already', true);

    expect(Subscriber::where('email', 'john@example.com')->count())->toBe(1);
});

it('resubscribes a previously unsubscribed email', function () {
    Subscriber::factory()->unsubscribed()->create(['email' => 'john@example.com']);

    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'john@example.com')
        ->call('subscribe')
        ->assertSet('done', true)
        ->assertSet('already', false);

    expect(Subscriber::where('email', 'john@example.com')->first()->status)->toBe('subscribed');
});

it('validates the email address', function () {
    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'email'])
        ->assertSet('done', false);
});

it('renders the newsletter box in the ecommerce footer', function () {
    $this->seed(RolePermissionSeeder::class);
    User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    get('/')
        ->assertOk()
        ->assertSee('Subscribe to our newsletter')
        ->assertSee('Your email address')
        ->assertSee('Subscribe', false);
});
