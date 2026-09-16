<?php

use App\Livewire\Admin\Coupons\Index as CouponsIndex;
use App\Models\Coupon;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $coupon = Coupon::factory()->create();

    Livewire::test(CouponsIndex::class)
        ->call('toggleSelect', $coupon->id)
        ->assertSee('Coupons')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $coupons = Coupon::factory()->count(2)->create();

    $component = Livewire::test(CouponsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $coupons[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the delete and export buttons only once a selection exists', function () {
    $coupon = Coupon::factory()->create();

    Livewire::test(CouponsIndex::class)
        ->assertDontSee('Export (')
        ->assertDontSee('Delete (')
        ->call('toggleSelect', $coupon->id)
        ->assertSee('Export (1)')
        ->assertSee('Delete (1)');
});

it('toggles a coupon id in and out of the selection', function () {
    $coupon = Coupon::factory()->create();

    Livewire::test(CouponsIndex::class)
        ->call('toggleSelect', $coupon->id)
        ->assertSet('selectedIds', [$coupon->id])
        ->call('toggleSelect', $coupon->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(CouponsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete modal once a selection exists', function () {
    $coupon = Coupon::factory()->create();

    Livewire::test(CouponsIndex::class)
        ->call('toggleSelect', $coupon->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'coupon-bulk-delete');
});

it('bulk deletes the selected coupons and clears the selection', function () {
    $toDelete = Coupon::factory()->count(2)->create();
    $keep = Coupon::factory()->create();

    Livewire::test(CouponsIndex::class)
        ->set('selectedIds', $toDelete->pluck('id')->all())
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify')
        ->assertDispatched('close-modal', name: 'coupon-bulk-delete');

    expect(Coupon::whereIn('id', $toDelete->pluck('id'))->count())->toBe(0);
    expect(Coupon::find($keep->id))->not->toBeNull();
});

it('disables the row actions dropdown trigger while a bulk selection is active', function () {
    $coupon = Coupon::factory()->create();

    Livewire::test(CouponsIndex::class)
        ->call('toggleSelect', $coupon->id)
        ->assertSeeHtml('disabled');
});
