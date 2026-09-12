<?php

use App\Livewire\Admin\ProductVendors\Form as ProductVendorForm;
use App\Livewire\Admin\ProductVendors\Index as ProductVendorIndex;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\User;
use App\Support\EnvFile;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — see
    // tests/Feature/Cms/AdminSettingsEnvTest.php for the same pattern.
    $this->envPath = sys_get_temp_dir().'/product-vendors-test-'.uniqid().'.env';
    file_put_contents($this->envPath, "APP_NAME=Test\nVENDOR_URL=\n");
    EnvFile::$pathOverride = $this->envPath;

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

afterEach(function () {
    EnvFile::$pathOverride = null;
    @unlink($this->envPath);
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

it('shows a Settings modal to edit the vendor portal url for an admin, but not for staff', function () {
    $this->get(route('admin.product-vendors'))
        ->assertOk()
        ->assertSee('vendor-url-settings', false);

    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.product-vendors'))
        ->assertOk()
        ->assertDontSee('vendor-url-settings', false);
});

it('blocks staff from saving the vendor portal url even by calling the component method directly', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    Livewire::actingAs($staff)
        ->test(ProductVendorIndex::class)
        ->set('vendorUrl', 'https://vendor.codeware.com')
        ->call('saveVendorUrl')
        ->assertForbidden();

    expect(EnvFile::get('VENDOR_URL'))->toBe('');
});

it('loads the current vendor portal url into the settings modal', function () {
    EnvFile::set(['VENDOR_URL' => 'https://vendor.codeware.test']);

    Livewire::test(ProductVendorIndex::class)
        ->assertSet('vendorUrl', 'https://vendor.codeware.test');
});

it('saves a new vendor portal url', function () {
    Livewire::test(ProductVendorIndex::class)
        ->set('vendorUrl', 'https://vendor.codeware.com')
        ->call('saveVendorUrl');

    expect(EnvFile::get('VENDOR_URL'))->toBe('https://vendor.codeware.com');
});

it('rejects an invalid vendor portal url', function () {
    Livewire::test(ProductVendorIndex::class)
        ->set('vendorUrl', 'not-a-url')
        ->call('saveVendorUrl')
        ->assertHasErrors(['vendorUrl']);
});

it('allows clearing the vendor portal url', function () {
    EnvFile::set(['VENDOR_URL' => 'https://vendor.codeware.test']);

    Livewire::test(ProductVendorIndex::class)
        ->set('vendorUrl', '')
        ->call('saveVendorUrl')
        ->assertHasNoErrors();

    expect(EnvFile::get('VENDOR_URL'))->toBe('');
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

it('creates a vendor with an address', function () {
    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Acme Supplies')
        ->set('address', '123 Market St')
        ->call('save');

    $vendor = ProductVendor::sole();
    expect($vendor->address)->toBe('123 Market St');
});

it('creates a vendor with a mobile number and email', function () {
    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Acme Supplies')
        ->set('mobile', '+880 1234-567890')
        ->set('email', 'acme@example.com')
        ->call('save');

    $vendor = ProductVendor::sole();
    expect($vendor->mobile)->toBe('+880 1234-567890')
        ->and($vendor->email)->toBe('acme@example.com');
});

it('rejects an invalid vendor email', function () {
    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Acme Supplies')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('loads an existing vendor\'s mobile and email into the form', function () {
    $vendor = ProductVendor::factory()->create(['mobile' => '+880 1234-567890', 'email' => 'acme@example.com']);

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->assertSet('mobile', '+880 1234-567890')
        ->assertSet('email', 'acme@example.com');
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
