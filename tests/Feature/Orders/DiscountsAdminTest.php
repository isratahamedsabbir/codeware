<?php

use App\Livewire\Admin\Discounts\Form as DiscountsForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders discounts index component', function () {
    Livewire::test(DiscountsIndex::class)->assertStatus(200);
});

it('displays existing discounts', function () {
    Discount::factory()->create(['name' => 'Winter Sale 20%']);

    Livewire::test(DiscountsIndex::class)->assertSee('Winter Sale 20%');
});

it('filters discounts by search, status and type', function () {
    Discount::factory()->active()->create(['name' => 'Active Sale', 'type' => 'percentage']);
    Discount::factory()->inactive()->create(['name' => 'Off Deal', 'type' => 'fixed']);

    Livewire::test(DiscountsIndex::class)
        ->set('search', 'active sale')
        ->assertSee('Active Sale')
        ->assertDontSee('Off Deal');

    Livewire::test(DiscountsIndex::class)
        ->set('statusFilter', 'inactive')
        ->assertSee('Off Deal')
        ->assertDontSee('Active Sale');

    Livewire::test(DiscountsIndex::class)
        ->set('typeFilter', 'fixed')
        ->assertSee('Off Deal')
        ->assertDontSee('Active Sale');
});

it('creates a discount and defaults it to inactive', function () {
    Livewire::test(DiscountsForm::class)
        ->set('name', 'New Year 30%')
        ->set('type', 'percentage')
        ->set('value', '30')
        ->call('save');

    $discount = Discount::where('name', 'New Year 30%')->firstOrFail();
    expect($discount->status)->toBe('inactive');
});

it('attaches products to a discount', function () {
    $products = Product::factory()->count(2)->create();

    Livewire::test(DiscountsForm::class)
        ->set('name', 'Bundle Deal')
        ->set('type', 'fixed')
        ->set('value', '200')
        ->set('product_ids', $products->pluck('id')->all())
        ->call('save');

    $discount = Discount::where('name', 'Bundle Deal')->firstOrFail();
    expect($discount->products()->pluck('products.id')->sort()->values()->all())
        ->toBe($products->pluck('id')->sort()->values()->all());
});

it('validates required fields', function () {
    Livewire::test(DiscountsForm::class)
        ->set('name', '')
        ->set('value', '')
        ->call('save')
        ->assertHasErrors(['name', 'value']);
});

it('rejects a percentage value over 100', function () {
    Livewire::test(DiscountsForm::class)
        ->set('name', 'Too Big')
        ->set('type', 'percentage')
        ->set('value', '150')
        ->call('save')
        ->assertHasErrors(['value']);
});

it('allows a fixed-amount value over 100', function () {
    Livewire::test(DiscountsForm::class)
        ->set('name', 'Big Fixed')
        ->set('type', 'fixed')
        ->set('value', '1500')
        ->call('save')
        ->assertHasNoErrors();

    expect(Discount::where('name', 'Big Fixed')->exists())->toBeTrue();
});

it('can edit a discount without touching its status', function () {
    $discount = Discount::factory()->active()->create(['name' => 'Old Deal', 'value' => 10]);

    Livewire::test(DiscountsForm::class, ['id' => $discount->id])
        ->set('value', '25')
        ->call('save');

    $discount->refresh();
    expect((float) $discount->value)->toBe(25.0)
        ->and($discount->status)->toBe('active');
});

it('toggles discount status from the index', function () {
    $discount = Discount::factory()->inactive()->create();

    Livewire::test(DiscountsIndex::class)->call('toggleStatus', $discount->id);

    expect($discount->refresh()->status)->toBe('active');
});

it('can delete a discount', function () {
    $discount = Discount::factory()->create();

    Livewire::test(DiscountsIndex::class)
        ->call('confirmDelete', $discount->id)
        ->call('delete');

    expect(Discount::find($discount->id))->toBeNull();
});

it('detaches products when a discount is deleted', function () {
    $discount = Discount::factory()->has(Product::factory(), 'products')->create();

    $discount->delete();

    expect(Discount::find($discount->id))->toBeNull();

    // The product itself survives — only the pivot link is removed.
    expect(Product::query()->count())->toBe(1);
});

it('computes the discounted price for percentage and fixed types', function () {
    $percentage = Discount::factory()->active()->create(['type' => 'percentage', 'value' => 20]);
    $fixed = Discount::factory()->active()->create(['type' => 'fixed', 'value' => 300]);

    expect($percentage->priceFor(1000))->toBe(800.0)
        ->and($fixed->priceFor(1000))->toBe(700.0)
        ->and($fixed->priceFor(200))->toBe(0.0); // never below zero
});

it('treats an inactive, not-yet-started, or expired discount as invalid', function () {
    $inactive = Discount::factory()->active()->create(['status' => 'inactive']);
    $upcoming = Discount::factory()->active()->create(['starts_at' => now()->addDays(2)]);
    $expired = Discount::factory()->active()->create(['ends_at' => now()->subDay()]);

    expect($inactive->isCurrentlyValid())->toBeFalse()
        ->and($upcoming->isCurrentlyValid())->toBeFalse()
        ->and($expired->isCurrentlyValid())->toBeFalse();

    $always = Discount::factory()->active()->create(['starts_at' => null, 'ends_at' => null]);
    expect($always->isCurrentlyValid())->toBeTrue();
});
