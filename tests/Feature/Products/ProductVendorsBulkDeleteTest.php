<?php

use App\Livewire\Admin\ProductVendors\Index as ProductVendorsIndex;
use App\Models\ProductVendor;
use App\Models\User;
use App\Support\EnvFile;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — see
    // tests/Feature/Cms/AdminSettingsEnvTest.php for the same pattern.
    $this->envPath = sys_get_temp_dir().'/product-vendors-bulk-test-'.uniqid().'.env';
    file_put_contents($this->envPath, "APP_NAME=Test\nVENDOR_URL=\n");
    EnvFile::$pathOverride = $this->envPath;

    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    EnvFile::$pathOverride = null;
    @unlink($this->envPath);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    // The breadcrumb is @included from inside this component's own template
    // (see the file header comment) rather than pushed into the layout, so
    // it must keep resolving to the real page — not Livewire's internal
    // update endpoint — on every subsequent request too.
    $vendor = ProductVendor::factory()->create();

    Livewire::test(ProductVendorsIndex::class)
        ->call('toggleSelect', $vendor->id)
        ->assertSee('Products')
        ->assertSee('Product Vendors')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $vendors = ProductVendor::factory()->count(2)->create();

    $component = Livewire::test(ProductVendorsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $vendors[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('toggles a vendor id in and out of the selection', function () {
    $vendor = ProductVendor::factory()->create();

    Livewire::test(ProductVendorsIndex::class)
        ->call('toggleSelect', $vendor->id)
        ->assertSet('selectedIds', [$vendor->id])
        ->call('toggleSelect', $vendor->id)
        ->assertSet('selectedIds', []);
});

it('shows the bulk action toolbar only once something is selected', function () {
    $vendor = ProductVendor::factory()->create();

    Livewire::test(ProductVendorsIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $vendor->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $vendor->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(ProductVendorsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $vendor = ProductVendor::factory()->create();

    Livewire::test(ProductVendorsIndex::class)
        ->call('toggleSelect', $vendor->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'product-vendor-bulk-delete');
});

it('deletes every selected vendor and clears the selection', function () {
    $vendors = ProductVendor::factory()->count(3)->create();
    $keep = ProductVendor::factory()->create();

    Livewire::test(ProductVendorsIndex::class)
        ->call('toggleSelect', $vendors[0]->id)
        ->call('toggleSelect', $vendors[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 vendors deleted successfully')
        ->assertDispatched('close-modal', name: 'product-vendor-bulk-delete');

    expect(ProductVendor::find($vendors[0]->id))->toBeNull()
        ->and(ProductVendor::find($vendors[1]->id))->toBeNull()
        ->and(ProductVendor::find($vendors[2]->id))->not->toBeNull()
        ->and(ProductVendor::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one vendor is deleted', function () {
    $vendor = ProductVendor::factory()->create();

    Livewire::test(ProductVendorsIndex::class)
        ->call('toggleSelect', $vendor->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 vendor deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $vendors = ProductVendor::factory()->count(2)->create();

    $component = Livewire::test(ProductVendorsIndex::class);

    $component->assertSeeHtml(route('admin.product-vendors.edit', $vendors[0]->id));

    $component->call('toggleSelect', $vendors[0]->id)
        ->assertDontSeeHtml(route('admin.product-vendors.edit', $vendors[0]->id))
        ->assertDontSeeHtml(route('admin.product-vendors.edit', $vendors[1]->id));
});
