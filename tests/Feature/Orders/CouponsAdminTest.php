<?php

use App\Livewire\Admin\Coupons\Form as CouponsForm;
use App\Livewire\Admin\Coupons\Index as CouponsIndex;
use App\Models\Coupon;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders coupons index component', function () {
    Livewire::test(CouponsIndex::class)->assertStatus(200);
});

it('displays existing coupons', function () {
    Coupon::factory()->create(['code' => 'SAVE20']);

    Livewire::test(CouponsIndex::class)->assertSee('SAVE20');
});

it('filters coupons by search and status', function () {
    Coupon::factory()->active()->create(['code' => 'ACTIVE10']);
    Coupon::factory()->inactive()->create(['code' => 'OFFCODE']);

    Livewire::test(CouponsIndex::class)
        ->set('search', 'active10')
        ->assertSee('ACTIVE10')
        ->assertDontSee('OFFCODE');

    Livewire::test(CouponsIndex::class)
        ->set('statusFilter', 'inactive')
        ->assertSee('OFFCODE')
        ->assertDontSee('ACTIVE10');
});

it('creates a coupon, stores the code uppercase, and defaults it to inactive', function () {
    Livewire::test(CouponsForm::class)
        ->set('code', 'newcode')
        ->set('type', 'percentage')
        ->set('value', '15')
        ->call('save');

    $coupon = Coupon::where('code', 'NEWCODE')->firstOrFail();
    expect($coupon->status)->toBe('inactive');
});

it('validates required fields and a unique code', function () {
    Coupon::factory()->create(['code' => 'TAKEN']);

    Livewire::test(CouponsForm::class)
        ->set('code', '')
        ->set('value', '')
        ->call('save')
        ->assertHasErrors(['code', 'value']);

    Livewire::test(CouponsForm::class)
        ->set('code', 'taken')
        ->set('type', 'percentage')
        ->set('value', '10')
        ->call('save')
        ->assertHasErrors(['code']);
});

it('rejects a percentage value over 100', function () {
    Livewire::test(CouponsForm::class)
        ->set('code', 'TOOBIG')
        ->set('type', 'percentage')
        ->set('value', '150')
        ->call('save')
        ->assertHasErrors(['value']);
});

it('allows a fixed-amount value over 100', function () {
    Livewire::test(CouponsForm::class)
        ->set('code', 'BIGFIXED')
        ->set('type', 'fixed')
        ->set('value', '500')
        ->call('save')
        ->assertHasNoErrors();

    expect(Coupon::where('code', 'BIGFIXED')->exists())->toBeTrue();
});

it('can edit a coupon without touching its status', function () {
    $coupon = Coupon::factory()->active()->create(['code' => 'OLDCODE', 'value' => 10]);

    Livewire::test(CouponsForm::class, ['id' => $coupon->id])
        ->set('value', '25')
        ->call('save');

    $coupon->refresh();
    expect((float) $coupon->value)->toBe(25.0)
        ->and($coupon->status)->toBe('active');
});

it('toggles coupon status from the index', function () {
    $coupon = Coupon::factory()->inactive()->create();

    Livewire::test(CouponsIndex::class)->call('toggleStatus', $coupon->id);

    expect($coupon->refresh()->status)->toBe('active');
});

it('can delete a coupon', function () {
    $coupon = Coupon::factory()->create();

    Livewire::test(CouponsIndex::class)
        ->call('confirmDelete', $coupon->id)
        ->call('delete');

    expect(Coupon::find($coupon->id))->toBeNull();
});

it('validates coupon amounts against an order total', function () {
    $percentage = Coupon::factory()->active()->create(['type' => 'percentage', 'value' => 10, 'min_order_amount' => 1000]);
    $fixed = Coupon::factory()->active()->create(['type' => 'fixed', 'value' => 200]);

    expect($percentage->isValidFor(500))->toBeFalse() // below min order
        ->and($percentage->isValidFor(2000))->toBeTrue()
        ->and($percentage->discountFor(2000))->toBe(200.0)
        ->and($fixed->discountFor(100))->toBe(100.0); // never exceeds the order total
});

it('treats an expired or exhausted coupon as invalid', function () {
    $expired = Coupon::factory()->active()->expired()->create();
    $exhausted = Coupon::factory()->active()->create(['max_uses' => 5, 'used_count' => 5]);

    expect($expired->isValidFor(1000))->toBeFalse()
        ->and($exhausted->isValidFor(1000))->toBeFalse();
});
