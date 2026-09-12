<?php

use App\Livewire\Admin\ProductBrands\Form as ProductBrandForm;
use App\Livewire\Admin\ProductBrands\Index as ProductBrandIndex;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

test('guests and non-admins are blocked from the product brands screen', function () {
    auth()->logout();
    $this->get(route('admin.product-brands'))->assertRedirect('/login');

    $this->actingAs(User::factory()->create(['is_admin' => false]));
    $this->get(route('admin.product-brands'))->assertForbidden();
});

it('renders the product brands index with existing brands', function () {
    ProductBrand::factory()->create(['name' => 'Acme']);

    Livewire::test(ProductBrandIndex::class)
        ->assertOk()
        ->assertSee('Acme');
});

it('filters brands by search', function () {
    ProductBrand::factory()->create(['name' => 'Acme']);
    ProductBrand::factory()->create(['name' => 'Globex']);

    Livewire::test(ProductBrandIndex::class)
        ->set('search', 'Acme')
        ->assertSee('Acme')
        ->assertDontSee('Globex');
});

it('creates a brand, active by default', function () {
    Livewire::test(ProductBrandForm::class)
        ->set('name', 'Acme')
        ->set('logo', '/storage/media/acme.png')
        ->call('save');

    $brand = ProductBrand::sole();
    expect($brand->name)->toBe('Acme')
        ->and($brand->logo)->toBe('/storage/media/acme.png')
        ->and($brand->status)->toBe('active');
});

it('rejects a duplicate brand name', function () {
    ProductBrand::factory()->create(['name' => 'Acme']);

    Livewire::test(ProductBrandForm::class)
        ->set('name', 'Acme')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('updates an existing brand, allowing it to keep its own name', function () {
    $brand = ProductBrand::factory()->create(['name' => 'Acme', 'status' => 'active']);

    Livewire::test(ProductBrandForm::class, ['id' => $brand->id])
        ->assertSet('name', 'Acme')
        ->set('name', 'Acme Corp')
        ->call('save');

    expect($brand->fresh()->name)->toBe('Acme Corp');
});

it('does not reset an inactive brand back to active when saved from the form', function () {
    $brand = ProductBrand::factory()->inactive()->create(['name' => 'Acme']);

    Livewire::test(ProductBrandForm::class, ['id' => $brand->id])
        ->set('name', 'Acme Corp')
        ->call('save');

    expect($brand->fresh()->status)->toBe('inactive');
});

it('toggles a brand\'s status from the index', function () {
    $brand = ProductBrand::factory()->create(['status' => 'active']);

    Livewire::test(ProductBrandIndex::class)
        ->call('toggleStatus', $brand->id);

    expect($brand->refresh()->status)->toBe('inactive');
});

it('deletes a brand, leaving products that used it with no brand', function () {
    $brand = ProductBrand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    Livewire::test(ProductBrandIndex::class)
        ->call('confirmDelete', $brand->id)
        ->call('delete');

    expect(ProductBrand::find($brand->id))->toBeNull();
    expect($product->fresh()->brand)->toBeNull();
});
