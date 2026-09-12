<?php

use App\Livewire\Admin\ProductVendors\Form as ProductVendorForm;
use App\Livewire\Admin\ProductVendors\Index as ProductVendorIndex;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

test('guests and non-admins are blocked from the product vendors screen', function () {
    auth()->logout();
    $this->get(route('admin.product-vendors'))->assertRedirect('/login');

    $this->actingAs(User::factory()->create(['is_admin' => false]));
    $this->get(route('admin.product-vendors'))->assertForbidden();
});

it('renders the product vendors index with existing vendors', function () {
    ProductVendor::factory()->create(['name' => 'Acme Supplies']);

    Livewire::test(ProductVendorIndex::class)
        ->assertOk()
        ->assertSee('Acme Supplies');
});

it('filters vendors by search', function () {
    ProductVendor::factory()->create(['name' => 'Acme Supplies']);
    ProductVendor::factory()->create(['name' => 'Globex Trading']);

    Livewire::test(ProductVendorIndex::class)
        ->set('search', 'Acme')
        ->assertSee('Acme Supplies')
        ->assertDontSee('Globex Trading');
});

it('creates a vendor, active by default', function () {
    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Acme Supplies')
        ->set('logo', '/storage/media/acme.png')
        ->call('save');

    $vendor = ProductVendor::sole();
    expect($vendor->name)->toBe('Acme Supplies')
        ->and($vendor->logo)->toBe('/storage/media/acme.png')
        ->and($vendor->status)->toBe('active');
});

it('creates a vendor with an address and assigned users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Acme Supplies')
        ->set('address', '123 Market St')
        ->set('user_ids', [$userA->id, $userB->id])
        ->call('save');

    $vendor = ProductVendor::sole();
    expect($vendor->address)->toBe('123 Market St');
    expect($vendor->users->pluck('id')->sort()->values()->all())->toBe([$userA->id, $userB->id]);
});

it('assigns the same user to more than one vendor', function () {
    $user = User::factory()->create();
    $vendorA = ProductVendor::factory()->create();

    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Second Vendor')
        ->set('user_ids', [$user->id])
        ->call('save');

    $vendorA->users()->attach($user);

    expect($user->fresh()->vendors()->count())->toBe(2);
});

it('updates a vendor\'s assigned users, removing ones no longer selected', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $vendor = ProductVendor::factory()->create();
    $vendor->users()->attach([$userA->id, $userB->id]);

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->assertSet('user_ids', fn ($ids) => in_array($userA->id, $ids) && in_array($userB->id, $ids))
        ->set('user_ids', [$userA->id])
        ->call('save');

    expect($vendor->fresh()->users->pluck('id')->all())->toBe([$userA->id]);
});

it('rejects a duplicate vendor name', function () {
    ProductVendor::factory()->create(['name' => 'Acme Supplies']);

    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Acme Supplies')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('updates an existing vendor, allowing it to keep its own name', function () {
    $vendor = ProductVendor::factory()->create(['name' => 'Acme Supplies', 'status' => 'active']);

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->assertSet('name', 'Acme Supplies')
        ->set('name', 'Acme Global Supplies')
        ->call('save');

    expect($vendor->fresh()->name)->toBe('Acme Global Supplies');
});

it('does not reset an inactive vendor back to active when saved from the form', function () {
    $vendor = ProductVendor::factory()->inactive()->create(['name' => 'Acme Supplies']);

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->set('name', 'Acme Global Supplies')
        ->call('save');

    expect($vendor->fresh()->status)->toBe('inactive');
});

it('toggles a vendor\'s status from the index', function () {
    $vendor = ProductVendor::factory()->create(['status' => 'active']);

    Livewire::test(ProductVendorIndex::class)
        ->call('toggleStatus', $vendor->id);

    expect($vendor->refresh()->status)->toBe('inactive');
});

it('deletes a vendor, leaving products that used it with no vendor', function () {
    $vendor = ProductVendor::factory()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::test(ProductVendorIndex::class)
        ->call('confirmDelete', $vendor->id)
        ->call('delete');

    expect(ProductVendor::find($vendor->id))->toBeNull();
    expect($product->fresh()->vendor)->toBeNull();
});
