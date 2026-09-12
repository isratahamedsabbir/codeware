<?php

use App\Livewire\Admin\ProductVendors\Form as ProductVendorForm;
use App\Livewire\Admin\ProductVendors\Index as ProductVendorIndex;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\User;
use App\Models\VendorDocument;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('shows a Settings shortcut to the vendor portal url for an admin, but not for staff', function () {
    $this->get(route('admin.product-vendors'))
        ->assertOk()
        ->assertSee(route('admin.settings').'?tab=env#VENDOR_URL', false);

    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.product-vendors'))
        ->assertOk()
        ->assertDontSee(route('admin.settings').'?tab=env#VENDOR_URL', false);
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

it('saves a signature drawn on the pad as an image file', function () {
    Storage::fake('public');
    $dataUri = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

    Livewire::test(ProductVendorForm::class)
        ->set('name', 'Acme Supplies')
        ->set('signature', $dataUri)
        ->call('save');

    $vendor = ProductVendor::sole();
    expect($vendor->signature)->not->toBeNull();
    Storage::disk('public')->assertExists($vendor->signature);
});

it('replaces the old signature file when a new one is saved', function () {
    Storage::fake('public');
    $vendor = ProductVendor::factory()->create(['signature' => 'signatures/old.png']);
    Storage::disk('public')->put('signatures/old.png', 'old-bytes');
    $dataUri = 'data:image/png;base64,'.base64_encode('new-png-bytes');

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->set('signature', $dataUri)
        ->call('save');

    Storage::disk('public')->assertMissing('signatures/old.png');
    expect($vendor->fresh()->signature)->not->toBe('signatures/old.png');
});

it('clears the signature when removed on the pad', function () {
    Storage::fake('public');
    $vendor = ProductVendor::factory()->create(['signature' => 'signatures/old.png']);
    Storage::disk('public')->put('signatures/old.png', 'old-bytes');

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->set('signature', null)
        ->call('save');

    expect($vendor->fresh()->signature)->toBeNull();
    Storage::disk('public')->assertMissing('signatures/old.png');
});

it('uploads documents against an existing vendor', function () {
    Storage::fake('public');
    $vendor = ProductVendor::factory()->create();

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->set('newDocuments', [
            UploadedFile::fake()->create('trade-license.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('nid.jpg'),
        ])
        ->call('uploadDocuments');

    expect($vendor->documents()->count())->toBe(2);
    $document = $vendor->documents()->where('name', 'trade-license.pdf')->sole();
    Storage::disk('public')->assertExists($document->file);
});

it('rejects a document of an unsupported file type', function () {
    Storage::fake('public');
    $vendor = ProductVendor::factory()->create();

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->set('newDocuments', [UploadedFile::fake()->create('malware.exe', 10)])
        ->call('uploadDocuments')
        ->assertHasErrors(['newDocuments.0']);

    expect($vendor->documents()->count())->toBe(0);
});

it('deletes a vendor document', function () {
    Storage::fake('public');
    $vendor = ProductVendor::factory()->create();
    Storage::disk('public')->put('vendor-documents/doc.pdf', 'contents');
    $document = VendorDocument::create(['vendor_id' => $vendor->id, 'name' => 'doc.pdf', 'file' => 'vendor-documents/doc.pdf']);

    Livewire::test(ProductVendorForm::class, ['id' => $vendor->id])
        ->call('deleteDocument', $document->id);

    expect(VendorDocument::find($document->id))->toBeNull();
    Storage::disk('public')->assertMissing('vendor-documents/doc.pdf');
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
