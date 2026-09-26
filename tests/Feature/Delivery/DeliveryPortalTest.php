<?php

use App\Livewire\Admin\Orders\Show as AdminOrderShow;
use App\Livewire\Delivery\Auth\Login;
use App\Livewire\Delivery\Dashboard;
use App\Livewire\Delivery\Orders\Index;
use App\Livewire\Delivery\Orders\Show;
use App\Livewire\Delivery\Profile;
use App\Mail\DeliveryOtpMail;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->rider = User::factory()->create(['password' => 'correct-password']);
    $this->rider->assignRole(['customer', 'delivery_boy']);
});

it('serves its own login page on the delivery host', function () {
    $this->get('http://'.config('app.delivery_host').'/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('logs a delivery boy in and lands them on their dashboard', function () {
    Livewire::test(Login::class)
        ->set('email', $this->rider->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertRedirect(route('delivery.dashboard'));

    expect(auth()->id())->toBe($this->rider->id);
});

it('rejects a customer without the delivery_boy role', function () {
    $customer = User::factory()->create(['password' => 'correct-password']);
    $customer->assignRole('customer');

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('denies portal access to a delivery boy who also holds an admin, staff or vendor role', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole(['delivery_boy', $role]);

    $this->actingAs($user)
        ->get('http://'.config('app.delivery_host').'/')
        ->assertForbidden();
})->with(['admin', 'staff', 'vendor']);

it('lists only the orders assigned to the signed-in rider', function () {
    $other = User::factory()->create()->assignRole('delivery_boy');

    Order::factory()->status('shipped')->create(['delivery_boy_id' => $this->rider->id, 'customer_name' => 'Mine Customer']);
    Order::factory()->create(['delivery_boy_id' => $other->id, 'customer_name' => 'Other Customer']);
    Order::factory()->create(['customer_name' => 'Unassigned Customer']);

    $this->actingAs($this->rider);

    Livewire::test(Index::class)
        ->assertSee('Mine Customer')
        ->assertDontSee('Other Customer')
        ->assertDontSee('Unassigned Customer');
});

it('404s on an order assigned to someone else', function () {
    $order = Order::factory()->create();

    $this->actingAs($this->rider)
        ->get('http://'.config('app.delivery_host').'/orders/'.$order->id)
        ->assertNotFound();
});

it('emails an OTP to the customer and marks the order delivered only with the right code', function () {
    Mail::fake();

    $order = Order::factory()->status('shipped')->create([
        'delivery_boy_id' => $this->rider->id,
        'customer_email' => 'buyer@example.com',
    ]);

    $this->actingAs($this->rider);

    $component = Livewire::test(Show::class, ['orderId' => $order->id])->call('sendOtp');

    $code = null;
    Mail::assertSent(DeliveryOtpMail::class, function (DeliveryOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return $mail->hasTo('buyer@example.com');
    });

    $wrong = $code === '111111' ? '222222' : '111111';

    $component->set('otp', $wrong)->call('confirmDelivery')->assertHasErrors('otp');
    expect($order->fresh()->status)->toBe('shipped');

    $component->set('otp', $code)->call('confirmDelivery')->assertHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe('delivered');
    expect($order->delivered_at)->not->toBeNull();
});

it('burns the code after too many wrong attempts', function () {
    Mail::fake();

    $order = Order::factory()->status('pending')->create(['delivery_boy_id' => $this->rider->id]);

    $this->actingAs($this->rider);

    $component = Livewire::test(Show::class, ['orderId' => $order->id])->call('sendOtp');

    $code = null;
    Mail::assertSent(DeliveryOtpMail::class, function (DeliveryOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $wrong = $code === '111111' ? '222222' : '111111';

    foreach (range(1, Show::MAX_ATTEMPTS) as $ignored) {
        $component->set('otp', $wrong)->call('confirmDelivery');
    }

    // Even the right code no longer works — a new one must be sent.
    $component->set('otp', $code)->call('confirmDelivery')->assertHasErrors('otp');
    expect($order->fresh()->status)->not->toBe('delivered');
});

it('lets an admin assign an order to a delivery boy', function () {
    $order = Order::factory()->status('pending')->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(AdminOrderShow::class, ['id' => $order->id])
        ->set('deliveryBoyId', (string) $this->rider->id)
        ->call('assignDeliveryBoy')
        ->assertHasNoErrors();

    expect($order->fresh()->delivery_boy_id)->toBe($this->rider->id);
});

it('refuses to assign an order to someone who is not a delivery boy', function () {
    $order = Order::factory()->status('pending')->create();
    $vendor = User::factory()->create();
    $vendor->assignRole(['delivery_boy', 'vendor']);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(AdminOrderShow::class, ['id' => $order->id])
        ->set('deliveryBoyId', (string) $vendor->id)
        ->call('assignDeliveryBoy')
        ->assertHasErrors('deliveryBoyId');

    expect($order->fresh()->delivery_boy_id)->toBeNull();
});

it('shows the rider their own delivery counts on the dashboard', function () {
    Order::factory()->status('shipped')->count(2)->create(['delivery_boy_id' => $this->rider->id]);
    Order::factory()->status('delivered')->create(['delivery_boy_id' => $this->rider->id, 'delivered_at' => now()]);
    Order::factory()->status('shipped')->count(3)->create();

    $this->actingAs($this->rider);

    Livewire::test(Dashboard::class)
        ->assertViewHas('toDeliverCount', 2)
        ->assertViewHas('deliveredTodayCount', 1)
        ->assertViewHas('deliveredCount', 1);
});

it('serves every portal page to a delivery boy', function (string $path) {
    $this->actingAs($this->rider)
        ->get('http://'.config('app.delivery_host').$path)
        ->assertOk();
})->with(['/', '/orders', '/profile']);

it('lets the rider update their own profile name', function () {
    $this->actingAs($this->rider);

    Livewire::test(Profile::class)
        ->set('name', 'Renamed Rider')
        ->call('updateProfile')
        ->assertHasNoErrors();

    expect($this->rider->fresh()->name)->toBe('Renamed Rider');
});

it('shows the Delivery Login link on the default theme only while a rider can sign in', function () {
    Setting::set('site_theme', 'default');

    $this->get('/')->assertOk()
        ->assertSee('Delivery Login')
        ->assertSee(route('delivery.login'));
});

it('hides the Delivery Login link as soon as the only rider is blocked', function () {
    Setting::set('site_theme', 'default');

    $this->rider->forceFill(['is_blocked' => true])->save();

    $this->get('/')->assertOk()->assertDontSee('Delivery Login');
});

it('hides the Delivery Login link once the delivery_boy role is deactivated', function () {
    Setting::set('site_theme', 'default');

    Role::where('name', 'delivery_boy')->update(['status' => 'inactive']);

    $this->get('/')->assertOk()->assertDontSee('Delivery Login');
});

it('hides the Delivery Login link when no user holds the delivery_boy role', function () {
    Setting::set('site_theme', 'default');

    $this->rider->removeRole('delivery_boy');

    $this->get('/')->assertOk()->assertDontSee('Delivery Login');
});
