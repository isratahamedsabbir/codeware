<?php

use App\Livewire\Admin\Users\Form as UsersForm;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\ProductVendor;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->role = Role::findOrCreate('manager', 'web');
});

it('renders users index', function () {
    Livewire::test(UsersIndex::class)->assertStatus(200);
});

it('displays users in the table', function () {
    User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    Livewire::test(UsersIndex::class)->assertSee('Jane Doe');
});

it('can filter users by role', function () {
    User::factory()->create(['name' => 'Manager User', 'email' => 'manager@example.com'])->assignRole('manager');
    User::factory()->create(['name' => 'Plain User', 'email' => 'plain@example.com']);

    Livewire::test(UsersIndex::class)
        ->set('roleFilter', 'manager')
        ->assertSee('Manager User')
        ->assertDontSee('Plain User');
});

it('renders user form for creation', function () {
    Livewire::test(UsersForm::class)->assertStatus(200);
});

it('renders user form for editing', function () {
    $user = User::factory()->create(['name' => 'Editable User', 'email' => 'editable@example.com']);
    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->assertStatus(200)
        ->assertSet('name', 'Editable User');
});

it('can create a user with a role', function () {
    Livewire::test(UsersForm::class)
        ->set('name', 'New Person')
        ->set('email', 'person@example.com')
        ->set('password', 'password123')
        ->set('selectedRoles', ['manager'])
        ->call('save');

    $user = User::where('email', 'person@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('manager'))->toBeTrue();
    expect($user->hasRole('admin'))->toBeFalsy();
});

it('can update a user and sync roles', function () {
    $user = User::factory()->create()->assignRole('manager');

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->set('name', 'Renamed User')
        ->set('email', 'renamed@example.com')
        ->set('selectedRoles', [])
        ->call('save');

    expect($user->fresh()->name)->toBe('Renamed User');
    expect($user->fresh()->email)->toBe('renamed@example.com');
    expect($user->fresh()->roles)->toBeEmpty();
});

it('validates unique email on update', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('assigns a user to more than one vendor when the vendor role is selected', function () {
    Role::findOrCreate('vendor', 'web');
    $vendorA = ProductVendor::factory()->create();
    $vendorB = ProductVendor::factory()->create();

    Livewire::test(UsersForm::class)
        ->set('name', 'Vendor Rep')
        ->set('email', 'rep@example.com')
        ->set('password', 'password123')
        ->set('selectedRoles', ['vendor'])
        ->set('vendor_ids', [$vendorA->id, $vendorB->id])
        ->call('save');

    $user = User::where('email', 'rep@example.com')->sole();
    expect($user->vendors->pluck('id')->sort()->values()->all())->toBe([$vendorA->id, $vendorB->id]);
});

it('updates a user\'s assigned vendors, removing ones no longer selected', function () {
    Role::findOrCreate('vendor', 'web');
    $vendorA = ProductVendor::factory()->create();
    $vendorB = ProductVendor::factory()->create();
    $user = User::factory()->create()->assignRole('vendor');
    $user->vendors()->attach([$vendorA->id, $vendorB->id]);

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->assertSet('vendor_ids', fn ($ids) => in_array($vendorA->id, $ids) && in_array($vendorB->id, $ids))
        ->set('vendor_ids', [$vendorA->id])
        ->call('save');

    expect($user->fresh()->vendors->pluck('id')->all())->toBe([$vendorA->id]);
});

it('revokes a user\'s vendor assignments when the vendor role is removed', function () {
    Role::findOrCreate('vendor', 'web');
    $vendor = ProductVendor::factory()->create();
    $user = User::factory()->create()->assignRole('vendor');
    $user->vendors()->attach($vendor->id);

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->set('selectedRoles', [])
        ->call('save');

    expect($user->fresh()->vendors)->toBeEmpty();
});

it('uploads documents against an existing user', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->set('newDocuments', [
            UploadedFile::fake()->create('nid.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('photo-id.jpg'),
        ])
        ->call('uploadDocuments');

    expect($user->documents()->count())->toBe(2);
    $document = $user->documents()->where('name', 'nid.pdf')->sole();
    Storage::disk('public')->assertExists($document->file);
});

it('rejects a document of an unsupported file type', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->set('newDocuments', [UploadedFile::fake()->create('malware.exe', 10)])
        ->call('uploadDocuments')
        ->assertHasErrors(['newDocuments.0']);

    expect($user->documents()->count())->toBe(0);
});

it('lists uploaded documents by name, without showing who uploaded them', function () {
    $user = User::factory()->create();
    $user->documents()->create(['name' => 'nid.pdf', 'file' => 'user-documents/x.pdf']);

    $html = Livewire::test(UsersForm::class, ['id' => $user->id])->html();

    expect($html)->toContain('nid.pdf')
        ->not->toContain('uploaded by')
        ->not->toContain('Uploaded by');
});

it('deletes a user document', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Storage::disk('public')->put('user-documents/doc.pdf', 'contents');
    $document = UserDocument::create(['user_id' => $user->id, 'name' => 'doc.pdf', 'file' => 'user-documents/doc.pdf']);

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->call('deleteDocument', $document->id);

    expect(UserDocument::find($document->id))->toBeNull();
    Storage::disk('public')->assertMissing('user-documents/doc.pdf');
});

it('cannot delete own account', function () {
    Livewire::test(UsersIndex::class)
        ->call('confirmDelete', $this->admin->id)
        ->call('delete');

    expect(User::find($this->admin->id))->not->toBeNull();
});

it('can delete another user', function () {
    $user = User::factory()->create();

    Livewire::test(UsersIndex::class)
        ->call('confirmDelete', $user->id)
        ->call('delete');

    expect(User::find($user->id))->toBeNull();
});

it('can block and unblock another user', function () {
    $user = User::factory()->create(['is_blocked' => false]);

    Livewire::test(UsersIndex::class)->call('toggleBlock', $user->id);
    expect($user->fresh()->is_blocked)->toBeTrue();

    Livewire::test(UsersIndex::class)->call('toggleBlock', $user->id);
    expect($user->fresh()->is_blocked)->toBeFalse();
});

it('cannot block own account', function () {
    Livewire::test(UsersIndex::class)->call('toggleBlock', $this->admin->id);

    expect($this->admin->fresh()->is_blocked)->toBeFalse();
});

it('ends the sessions of a user when they are blocked', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 'session-blocked-user', 'user_id' => $user->id,
        'ip_address' => '127.0.0.1', 'user_agent' => 'test',
        'payload' => 'x', 'last_activity' => time(),
    ]);

    Livewire::test(UsersIndex::class)->call('toggleBlock', $user->id);

    expect(DB::table('sessions')->where('id', 'session-blocked-user')->exists())->toBeFalse();
});

it('does not touch sessions when a user is unblocked', function () {
    $user = User::factory()->create(['is_blocked' => true]);
    DB::table('sessions')->insert([
        'id' => 'session-unblocked-user', 'user_id' => $user->id,
        'ip_address' => '127.0.0.1', 'user_agent' => 'test',
        'payload' => 'x', 'last_activity' => time(),
    ]);

    Livewire::test(UsersIndex::class)->call('toggleBlock', $user->id);

    expect($user->fresh()->is_blocked)->toBeFalse();
    expect(DB::table('sessions')->where('id', 'session-unblocked-user')->exists())->toBeTrue();
});
