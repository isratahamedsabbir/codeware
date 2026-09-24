<?php

use App\Livewire\Admin\Orders\Show as AdminOrderShow;
use App\Livewire\Delivery\Auth\Login;
use App\Livewire\Delivery\Orders\Index;
use App\Livewire\Delivery\Orders\Show;
use App\Mail\DeliveryOtpMail;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->rider = User::factory()->create(['password' => 'correct-password', 'is_delivery_boy' => true]);
    $this->rider->assignRole('customer');
});

it('serves its own login page on the delivery host', function () {
    $this->get('http://'.config('app.delivery_host').'/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('logs a delivery boy in and lands them on their orders', function () {
    Livewire::test(Login::class)
        ->set('email', $this->rider->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertRedirect(route('delivery.orders'));

    expect(auth()->id())->toBe($this->rider->id);
});

it('rejects a customer who is not a delivery boy', function () {
    $customer = User::factory()->create(['password' => 'correct-password']);
    $customer->assignRole('customer');

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('denies portal access to a flagged account that also holds an admin, staff or vendor role', function (string $role) {
    $user = User::factory()->create(['is_delivery_boy' => true]);
    $user->assignRole($role);

    $this->actingAs($user)
        ->get('http://'.config('app.delivery_host').'/')
        ->assertForbidden();
})->with(['admin', 'staff', 'vendor']);

it('lists only the orders assigned to the signed-in rider', function () {
    $other = User::factory()->create(['is_delivery_boy' => true]);

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
    $vendor = User::factory()->create(['is_delivery_boy' => true]);
    $vendor->assignRole('vendor');

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(AdminOrderShow::class, ['id' => $order->id])
        ->set('deliveryBoyId', (string) $vendor->id)
        ->call('assignDeliveryBoy')
        ->assertHasErrors('deliveryBoyId');

    expect($order->fresh()->delivery_boy_id)->toBeNull();
});
