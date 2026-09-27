<?php

use App\Livewire\Admin\Bookings\Index as BookingsIndex;
use App\Livewire\Frontend\BookService;
use App\Mail\TemplateDrivenMail;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminAlert;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/*
 * Booking a service from the portfolio storefront.
 *
 * The behaviour that matters most here is the one that is easy to get wrong:
 * the service id arrives from the browser, so it has to be re-checked on submit
 * rather than trusted. Everything else is a form, and a form is easy.
 */

beforeEach(function () {
    // Both the storefront and the admin bookings route sit behind a feature
    // toggle. Features::enabled() defaults to true for anything not configured,
    // so a fresh test database is already in the "everything on" state and
    // there is nothing to switch on here.
    Setting::set('site_theme', 'portfolio');
});

it('stores a booking against the service the card was for', function () {
    $service = Service::factory()->published()->create([
        'name' => ['en' => 'Laravel Application Development', 'bn' => ''],
    ]);

    Livewire::test(BookService::class, ['serviceId' => $service->id])
        ->set('fullName', 'Nayeem Rahman')
        ->set('email', 'nayeem@example.com')
        ->set('phoneNumber', '+880 1711000000')
        ->set('message', 'Need an admin panel for a small factory.')
        ->call('book')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $booking = Booking::sole();

    expect($booking->service_id)->toBe($service->id)
        ->and($booking->full_name)->toBe('Nayeem Rahman')
        ->and($booking->email)->toBe('nayeem@example.com')
        ->and($booking->phone_number)->toBe('+880 1711000000')
        ->and($booking->message)->toBe('Need an admin panel for a small factory.');

    // No date is stored, because none was promised: the status is the only
    // state a booking starts in, and it is "nobody has got to this yet".
    expect($booking->status)->toBe(Booking::STATUS_NEW)
        ->and(Schema::hasColumn('bookings', 'booked_for'))->toBeFalse()
        ->and(Schema::hasColumn('bookings', 'scheduled_at'))->toBeFalse();
});

it('refuses a tampered service id rather than booking something inactive', function () {
    // The id is a public property, so it is whatever the browser says. A retired
    // service must not be bookable even if its id is posted directly.
    $retired = Service::factory()->draft()->create();

    Livewire::test(BookService::class, ['serviceId' => $retired->id])
        ->set('fullName', 'Nayeem Rahman')
        ->set('email', 'nayeem@example.com')
        ->call('book')
        ->assertHasErrors('serviceId');

    expect(Booking::count())->toBe(0);
});

it('requires a name and a valid email, and treats the rest as optional', function () {
    $service = Service::factory()->published()->create();

    Livewire::test(BookService::class, ['serviceId' => $service->id])
        ->call('book')
        ->assertHasErrors(['fullName', 'email'])
        ->assertHasNoErrors(['phoneNumber', 'message']);

    expect(Booking::count())->toBe(0);
});

it('notifies the admins in the bell and by email', function () {
    Notification::fake();
    Mail::fake();

    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create(['email' => 'owner@codeware.test']);
    User::factory()->create(['email' => 'nobody@codeware.test']);

    $service = Service::factory()->published()->create([
        'name' => ['en' => 'Storefronts & Interfaces', 'bn' => ''],
    ]);

    Livewire::test(BookService::class, ['serviceId' => $service->id])
        ->set('fullName', 'Nayeem Rahman')
        ->set('email', 'nayeem@example.com')
        ->call('book')
        ->assertHasNoErrors();

    Notification::assertSentTo($admin, AdminAlert::class, fn (AdminAlert $alert) => $alert->link === route('admin.bookings'));

    // Mail is the channel that actually reaches someone, so it goes too — and to
    // the same admins, so there is one place to change who is told.
    Mail::assertSent(TemplateDrivenMail::class, 1);
});

it('still stores the booking when the notification cannot be delivered', function () {
    // A mail failure must not roll back the row: it is already stored, the
    // admin inbox still has it, and losing a real request over a transport
    // problem is the worse outcome by a long way.
    Notification::fake();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP is down'));

    $service = Service::factory()->published()->create();

    Livewire::test(BookService::class, ['serviceId' => $service->id])
        ->set('fullName', 'Nayeem Rahman')
        ->set('email', 'nayeem@example.com')
        ->call('book');

    expect(Booking::count())->toBe(1);
});

it('offers a booking form on the portfolio page, once per active service', function () {
    Service::factory()->published()->create();
    Service::factory()->published()->create();
    Service::factory()->draft()->create();

    $html = $this->get('/')->assertOk()->getContent();

    // Counted on the form, not on the component name: Livewire prints the
    // component name more than once per instance (in the wire:snapshot and the
    // surrounding markup), so a name count reads as twice the cards there are.
    expect(substr_count($html, 'wire:submit="book"'))->toBe(2)
        ->and($html)->not->toContain('This service is no longer available.');
});

it('shows the owner the bookings in the admin, and lets them mark one done', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    $service = Service::factory()->published()->create([
        'name' => ['en' => 'Laravel Application Development', 'bn' => ''],
    ]);

    $booking = Booking::create([
        'service_id' => $service->id,
        'full_name' => 'Nayeem Rahman',
        'email' => 'nayeem@example.com',
        'message' => 'Need an admin panel.',
    ]);

    Livewire::test(BookingsIndex::class)
        ->assertSee('Nayeem Rahman')
        ->assertSee('Laravel Application Development')
        ->call('toggleStatus', $booking->id)
        ->assertDispatched('notify');

    expect($booking->fresh()->status)->toBe(Booking::STATUS_COMPLETED);

    // And back again, because a mark applied to the wrong row has to be fixable.
    Livewire::test(BookingsIndex::class)->call('toggleStatus', $booking->id);

    expect($booking->fresh()->status)->toBe(Booking::STATUS_NEW);
});

it('keeps a deleted service from erasing the request that named it', function () {
    $service = Service::factory()->published()->create();

    $booking = Booking::create([
        'service_id' => $service->id,
        'full_name' => 'Nayeem Rahman',
        'email' => 'nayeem@example.com',
    ]);

    $service->delete();

    // Soft-deleted services keep their bookings, and the admin list names the
    // service as gone rather than dropping the row: the name, the email and the
    // message are all still worth reading.
    expect(Booking::count())->toBe(1)
        ->and($booking->fresh()->service)->toBeNull();
});

it('counts only the unread ones in the admin filter', function () {
    $service = Service::factory()->published()->create();

    Booking::create(['service_id' => $service->id, 'full_name' => 'A', 'email' => 'a@example.com']);
    Booking::create(['service_id' => $service->id, 'full_name' => 'B', 'email' => 'b@example.com']);
    Booking::create([
        'service_id' => $service->id,
        'full_name' => 'C',
        'email' => 'c@example.com',
        'status' => Booking::STATUS_COMPLETED,
    ]);

    expect(Booking::new()->count())->toBe(2);
});
