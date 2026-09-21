<?php

use App\Livewire\Admin\ProductBrands\Form as ProductBrandForm;
use App\Livewire\Admin\ProductBrands\Index as ProductBrandIndex;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('guests and non-admins are blocked from the product brands screen', function () {
    auth()->logout();
    $this->get(route('admin.product-brands'))->assertRedirect('/login');

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.product-brands'))->assertForbidden();
});

it('renders the product brands index with existing brands', function () {
    ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);

    Livewire::test(ProductBrandIndex::class)
        ->assertOk()
        ->assertSee('Acme');
});

it('shows a "Both" badge for a shared (null-type) brand on the index, never mislabeled as Product', function () {
    ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => ''], 'type' => null]);

    $html = Livewire::test(ProductBrandIndex::class)->html();

    expect($html)->toContain('Both');
});

it('filters the index to shared (null-type) brands only', function () {
    ProductBrand::factory()->create(['name' => ['en' => 'Shared Co', 'bn' => ''], 'type' => null]);
    ProductBrand::factory()->create(['name' => ['en' => 'Product Only', 'bn' => ''], 'type' => ProductBrand::TYPE_PRODUCT]);

    Livewire::test(ProductBrandIndex::class)
        ->set('typeFilter', 'shared')
        ->assertSee('Shared Co')
        ->assertDontSee('Product Only');
});

it('filters brands by search', function () {
    ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);
    ProductBrand::factory()->create(['name' => ['en' => 'Globex', 'bn' => '']]);

    Livewire::test(ProductBrandIndex::class)
        ->set('search', 'Acme')
        ->assertSee('Acme')
        ->assertDontSee('Globex');
});

it('creates a brand, active by default', function () {
    Livewire::test(ProductBrandForm::class)
        ->set('name.en', 'Acme')
        ->set('logo', '/storage/media/acme.png')
        ->call('save');

    $brand = ProductBrand::sole();
    expect($brand->name)->toBe('Acme')
        ->and($brand->logo)->toBe('/storage/media/acme.png')
        ->and($brand->status)->toBe('active');
});

it('can create a shared (null-type) brand by picking the Shared option from the form', function () {
    Livewire::test(ProductBrandForm::class)
        ->set('name.en', 'Acme')
        ->set('type', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(ProductBrand::sole()->type)->toBeNull();
});

it('loads an existing shared (null-type) brand with the Shared option selected', function () {
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => ''], 'type' => null]);

    Livewire::test(ProductBrandForm::class, ['id' => $brand->id])
        ->assertSet('type', '');
});

it('rejects a duplicate brand name', function () {
    ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);

    Livewire::test(ProductBrandForm::class)
        ->set('name.en', 'Acme')
        ->call('save')
        ->assertHasErrors(['name.en']);
});

it('updates an existing brand, allowing it to keep its own name', function () {
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => ''], 'status' => 'active']);

    Livewire::test(ProductBrandForm::class, ['id' => $brand->id])
        ->assertSet('name.en', 'Acme')
        ->set('name.en', 'Acme Corp')
        ->call('save');

    expect($brand->fresh()->name)->toBe('Acme Corp');
});

it('does not reset an inactive brand back to active when saved from the form', function () {
    $brand = ProductBrand::factory()->inactive()->create(['name' => ['en' => 'Acme', 'bn' => '']]);

    Livewire::test(ProductBrandForm::class, ['id' => $brand->id])
        ->set('name.en', 'Acme Corp')
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
