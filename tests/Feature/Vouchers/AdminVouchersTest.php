<?php

use App\Livewire\Admin\Vouchers\Form as VoucherForm;
use App\Livewire\Admin\Vouchers\Index as VoucherIndex;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherPurchase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders the vouchers index component', function () {
    Livewire::test(VoucherIndex::class)->assertStatus(200);
});

it('displays existing vouchers', function () {
    Voucher::factory()->create(['name' => ['en' => 'Birthday Voucher', 'bn' => '']]);

    Livewire::test(VoucherIndex::class)->assertSee('Birthday Voucher');
});

it('filters vouchers by status', function () {
    Voucher::factory()->active()->create(['name' => ['en' => 'Active One', 'bn' => '']]);
    Voucher::factory()->inactive()->create(['name' => ['en' => 'Off One', 'bn' => '']]);

    Livewire::test(VoucherIndex::class)
        ->set('statusFilter', 'inactive')
        ->assertSee('Off One')
        ->assertDontSee('Active One');
});

it('creates a voucher, generating its slug, and defaults it to inactive', function () {
    Livewire::test(VoucherForm::class)
        ->set('name.en', 'Gift Voucher 500')
        ->set('price', '500')
        ->set('value', '500')
        ->set('currency', 'BDT')
        ->call('save')
        ->assertHasNoErrors();

    $voucher = Voucher::where('slug', 'gift-voucher-500')->firstOrFail();

    expect($voucher->status)->toBe('inactive')
        ->and($voucher->currency)->toBe('BDT');
});

it('requires a name, price and value, and a unique slug', function () {
    Voucher::factory()->create(['slug' => 'taken-slug']);

    Livewire::test(VoucherForm::class)
        ->set('name.en', '')
        ->set('price', '')
        ->set('value', '')
        ->call('save')
        ->assertHasErrors(['name.en', 'price', 'value']);

    Livewire::test(VoucherForm::class)
        ->set('name.en', 'Taken Slug')
        ->set('slug', 'taken-slug')
        ->set('price', '100')
        ->set('value', '100')
        ->set('currency', 'BDT')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('edits a voucher without touching its status', function () {
    $voucher = Voucher::factory()->active()->create(['price' => 100]);

    Livewire::test(VoucherForm::class, ['id' => $voucher->id])
        ->set('price', '250')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $voucher->refresh()->price)->toBe(250.0)
        ->and($voucher->status)->toBe('active');
});

it('toggles voucher status from the index', function () {
    $voucher = Voucher::factory()->inactive()->create();

    Livewire::test(VoucherIndex::class)->call('toggleStatus', $voucher->id);

    expect($voucher->refresh()->status)->toBe('active');
});

it('soft deletes a voucher but keeps the vouchers already sold from it', function () {
    $voucher = Voucher::factory()->create();
    $purchase = VoucherPurchase::factory()->create(['voucher_id' => $voucher->id]);

    Livewire::test(VoucherIndex::class)
        ->call('confirmDelete', $voucher->id)
        ->call('delete');

    expect(Voucher::find($voucher->id))->toBeNull()
        ->and(Voucher::withTrashed()->find($voucher->id))->not->toBeNull()
        ->and($purchase->refresh()->voucher_id)->toBe($voucher->id)
        ->and($purchase->voucher)->not->toBeNull();
});
