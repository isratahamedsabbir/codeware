<?php

use App\Livewire\Admin\Types\Form as TypeForm;
use App\Livewire\Admin\Types\Index as TypeIndex;
use App\Models\Category;
use App\Models\PostCategory;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Tag;
use App\Models\Type;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->productTypeId = Type::idFor(Type::PRODUCT);
    $this->postTypeId = Type::idFor(Type::POST);
});

test('guests and non-admins are blocked from the types screen', function () {
    auth()->logout();
    $this->get(route('admin.types'))->assertRedirect('/login');

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.types'))->assertForbidden();
});

it('renders the types index with the seeded pools and their usage counts', function () {
    Category::factory()->create(['name' => ['en' => 'Appliances', 'bn' => '']]);
    Tag::factory()->create(['name' => ['en' => 'Sale', 'bn' => '']]);

    $html = Livewire::test(TypeIndex::class)->assertOk()->html();

    expect($html)->toContain('Product')
        ->and($html)->toContain('Post');
});

it('searches types by name and slug', function () {
    Livewire::test(TypeIndex::class)
        ->set('search', 'post')
        ->assertOk();
});

it('creates a type, deriving the slug from the primary name', function () {
    Livewire::test(TypeForm::class)
        ->set('name.en', 'Product Category')
        ->set('name.bn', '')
        ->set('sortOrder', 3)
        ->call('save')
        ->assertHasNoErrors();

    $type = Type::where('slug', 'product-category')->sole();

    expect($type->name)->toBe('Product Category')
        ->and($type->slug)->toBe('product-category')
        ->and($type->sort_order)->toBe(3)
        ->and($type->status)->toBe('active');
});

it('rejects a slug that collides with an existing type, since slugs are the stable key', function () {
    Livewire::test(TypeForm::class)
        ->set('name.en', 'Something Else')
        ->set('slug', Type::PRODUCT)
        ->call('save')
        ->assertHasErrors(['slug']);
});

// The model derives a blank slug from the name on save. If the form left it
// blank the unique rule would have compared NULL and matched nothing, so the
// collision only surfaced as a raw 500 from the unique index at INSERT time.
it('rejects a blank slug that would derive a colliding one', function () {
    Livewire::test(TypeForm::class)
        ->set('name.en', 'Product')
        ->set('slug', '')
        ->call('save')
        ->assertHasErrors(['slug']);

    expect(Type::where('slug', Type::PRODUCT)->count())->toBe(1);
});

it('rejects a duplicate primary-locale name', function () {
    Livewire::test(TypeForm::class)
        ->set('name.en', 'Post')
        ->call('save')
        ->assertHasErrors(['name.en']);
});

it('edits a type without reactivating a deactivated one', function () {
    $type = Type::findOrFail($this->postTypeId);
    $type->update(['status' => 'inactive']);

    Livewire::test(TypeForm::class, ['id' => $type->id])
        ->set('name.en', 'Blog')
        ->call('save')
        ->assertHasNoErrors();

    expect($type->fresh()->name)->toBe('Blog')
        ->and($type->fresh()->status)->toBe('inactive');
});

it('toggles a type status from the index', function () {
    Livewire::test(TypeIndex::class)
        ->call('toggleStatus', $this->productTypeId)
        ->assertHasNoErrors();

    expect(Type::findOrFail($this->productTypeId)->status)->toBe('inactive');

    Livewire::test(TypeIndex::class)
        ->call('toggleStatus', $this->productTypeId)
        ->assertHasNoErrors();

    expect(Type::findOrFail($this->productTypeId)->status)->toBe('active');
});

it('refuses to delete a type that categories, brands or tags still use', function () {
    Tag::factory()->create(['name' => ['en' => 'Featured', 'bn' => '']]);

    Livewire::test(TypeIndex::class)
        ->call('confirmDelete', $this->productTypeId)
        ->call('delete')
        ->assertDispatched('notify', fn ($event, $params) => ($params['message'] ?? '') === 'Product is still assigned to categories, brands or tags. Deactivate it instead.');

    expect(Type::find($this->productTypeId))->not->toBeNull();
});

it('deletes an unused type', function () {
    $type = Type::create(['name' => ['en' => 'Dormant', 'bn' => ''], 'status' => 'active']);

    Livewire::test(TypeIndex::class)
        ->call('confirmDelete', $type->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Type::find($type->id))->toBeNull();
});

it('bulk-deletes only the unused types and reports what it skipped', function () {
    $unused = Type::create(['name' => ['en' => 'Dormant', 'bn' => ''], 'status' => 'active']);
    ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);

    Livewire::test(TypeIndex::class)
        ->set('selectedIds', [$unused->id, $this->productTypeId])
        ->call('bulkDelete')
        // One summary notification, not one per skipped row.
        ->assertDispatched('notify', fn ($event, $params) => $params['message'] === '1 type deleted successfully — 1 still in use, deactivate it instead')
        ->assertDispatched('notify', fn ($event, $params) => ! str_contains($params['message'], 'still assigned to'));

    expect(Type::find($unused->id))->toBeNull()
        ->and(Type::find($this->productTypeId))->not->toBeNull();
});

it('keeps a deactivated type visible as a selection on taxonomy forms', function () {
    // Otherwise saving a category whose type was pulled from circulation would
    // quietly move it to whatever else happened to be listed.
    Type::findOrFail($this->postTypeId)->update(['status' => 'inactive']);

    expect(Type::selectOptions()->pluck('id'))->toContain($this->postTypeId);
});

// type_id is NOT NULL, and a brand or tag's pool is a per-row choice rather than
// a property of the model — so every create() that skips it needs somewhere to
// land, exactly as the migration had to place every legacy row.
it('falls back to the product pool when a create() names no type', function () {
    $tag = Tag::create(['name' => ['en' => 'Unsorted', 'bn' => ''], 'status' => 'active']);
    $brand = ProductBrand::create(['name' => ['en' => 'Unsorted Brand', 'bn' => ''], 'status' => 'active']);

    expect($tag->type_id)->toBe($this->productTypeId)
        ->and($brand->type_id)->toBe($this->productTypeId);
});

it('never lets a pool-locked model be created into the wrong pool', function () {
    // The base Category fallback must not win over the subclass's own pool.
    expect(PostCategory::create(['name' => ['en' => 'News', 'bn' => ''], 'status' => 'active'])->type_id)
        ->toBe($this->postTypeId)
        ->and(ProductCategory::create(['name' => ['en' => 'Kitchen', 'bn' => ''], 'status' => 'active'])->type_id)
        ->toBe($this->productTypeId);
});
