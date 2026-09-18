<?php

use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherPurchase;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
});

it('serves the public voucher page for a validly signed url', function () {
    $purchase = VoucherPurchase::factory()->create();

    $url = URL::signedRoute('vouchers.public.show', ['voucher' => $purchase->code]);

    $this->get($url)
        ->assertOk()
        ->assertSee($purchase->code)
        ->assertSee('data:image/png;base64,', false);
});

it('rejects the public voucher page without a valid signature', function () {
    $purchase = VoucherPurchase::factory()->create();

    $this->get('/vouchers/'.$purchase->code)->assertForbidden();
    $this->get('/vouchers/'.$purchase->code.'?signature=tampered')->assertForbidden();
});

it('downloads the voucher pdf from a validly signed url', function () {
    $purchase = VoucherPurchase::factory()->create();

    $url = URL::signedRoute('vouchers.public.download', ['voucher' => $purchase->code]);

    $response = $this->get($url);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('lets an admin download an issued voucher pdf', function () {
    $admin = User::factory()->admin()->create();
    $purchase = VoucherPurchase::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.voucher-purchases.download', $purchase));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('lets an admin view an issued voucher pdf inline', function () {
    $admin = User::factory()->admin()->create();
    $purchase = VoucherPurchase::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.voucher-purchases.view', $purchase));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline');
});

it('blocks staff from the admin voucher download route', function () {
    Role::findOrCreate('staff', 'web');
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $purchase = VoucherPurchase::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.voucher-purchases.download', $purchase))
        ->assertForbidden();
});

it('expires the issued voucher based on the product validity window', function () {
    $voucher = Voucher::factory()->create(['valid_days' => 10]);
    $purchase = VoucherPurchase::factory()->create(['voucher_id' => $voucher->id]);

    expect($purchase->expires_at)->not->toBeNull()
        ->and((int) $purchase->purchased_at->diffInDays($purchase->expires_at))->toBe(10);
});

it('leaves the issued voucher without an expiry when the product never expires', function () {
    $voucher = Voucher::factory()->create(['valid_days' => null]);
    $purchase = VoucherPurchase::factory()->create(['voucher_id' => $voucher->id]);

    expect($purchase->expires_at)->toBeNull();
});

it('reports the savings between price and face value', function () {
    $voucher = Voucher::factory()->create(['price' => 900, 'value' => 1000]);

    expect($voucher->savings())->toBe(100.0)
        ->and(Voucher::factory()->make(['price' => 1000, 'value' => 500])->savings())->toBe(0.0);
});
