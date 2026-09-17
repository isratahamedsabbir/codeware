<?php

use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Models\Discount;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $discount = Discount::factory()->create();

    Livewire::test(DiscountsIndex::class)
        ->call('toggleSelect', $discount->id)
        ->assertSee('Discounts')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $discounts = Discount::factory()->count(2)->create();

    $component = Livewire::test(DiscountsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $discounts[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the delete button only once a selection exists', function () {
    $discount = Discount::factory()->create();

    Livewire::test(DiscountsIndex::class)
        ->assertDontSee('Delete (')
        ->call('toggleSelect', $discount->id)
        ->assertSee('Delete (1)');
});

it('toggles a discount id in and out of the selection', function () {
    $discount = Discount::factory()->create();

    Livewire::test(DiscountsIndex::class)
        ->call('toggleSelect', $discount->id)
        ->assertSet('selectedIds', [$discount->id])
        ->call('toggleSelect', $discount->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(DiscountsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete modal once a selection exists', function () {
    $discount = Discount::factory()->create();

    Livewire::test(DiscountsIndex::class)
        ->call('toggleSelect', $discount->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'discount-bulk-delete');
});

it('bulk deletes the selected discounts and clears the selection', function () {
    $toDelete = Discount::factory()->count(2)->create();
    $keep = Discount::factory()->create();

    Livewire::test(DiscountsIndex::class)
        ->set('selectedIds', $toDelete->pluck('id')->all())
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify')
        ->assertDispatched('close-modal', name: 'discount-bulk-delete');

    expect(Discount::whereIn('id', $toDelete->pluck('id'))->count())->toBe(0);
    expect(Discount::find($keep->id))->not->toBeNull();
});

it('disables the row actions dropdown trigger while a bulk selection is active', function () {
    $discount = Discount::factory()->create();

    Livewire::test(DiscountsIndex::class)
        ->call('toggleSelect', $discount->id)
        ->assertSeeHtml('disabled');
});
