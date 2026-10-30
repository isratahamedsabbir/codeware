<?php

use App\Livewire\Admin\Cruds\Form;
use App\Livewire\Admin\Cruds\Index;
use App\Livewire\Admin\Cruds\Records;
use App\Models\Crud;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

function makeCrud(): void
{
    Livewire::test(Form::class)
        ->set('name', 'Testimonials')
        ->set('fields', [
            ['label' => 'Customer name', 'type' => 'text', 'required' => true, 'in_list' => true, 'options' => ''],
            ['label' => 'Rating', 'type' => 'select', 'required' => false, 'in_list' => true, 'options' => '1,2,3'],
            ['label' => 'Featured', 'type' => 'boolean', 'required' => false, 'in_list' => true, 'options' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();
}

it('creates the table, the definition and the sidebar link', function () {
    makeCrud();

    expect(Schema::hasColumns('crud_testimonials', ['customer_name', 'rating', 'featured']))->toBeTrue();
    expect(Crud::where('slug', 'testimonials')->exists())->toBeTrue();

    $labels = MenuItem::menuForCurrentUser()->firstWhere('label', 'CRUD')->children->pluck('label');
    expect($labels->all())->toBe(['CRUD Builder', 'Testimonials']);
});

it('runs records through list, create, edit and delete', function () {
    makeCrud();

    Livewire::test(Records::class, ['slug' => 'testimonials'])
        ->call('create')
        ->call('save')
        ->assertHasErrors(['form.customer_name' => 'required'])
        ->set('form.customer_name', 'Rina')
        ->set('form.rating', '3')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Rina');

    $id = DB::table('crud_testimonials')->value('id');

    Livewire::test(Records::class, ['slug' => 'testimonials'])
        ->call('edit', $id)
        ->set('form.customer_name', 'Rina K')
        ->call('save')
        ->call('confirmDelete', $id)->call('delete');

    expect(DB::table('crud_testimonials')->count())->toBe(0);
});

it('adds fields on edit and drops the table on delete', function () {
    makeCrud();
    $crud = Crud::first();

    Livewire::test(Form::class, ['id' => $crud->id])
        ->call('addField')
        ->set('fields.3.label', 'Quote')
        ->set('fields.3.type', 'textarea')
        ->call('save')
        ->assertHasNoErrors();

    expect(Schema::hasColumn('crud_testimonials', 'quote'))->toBeTrue();

    Livewire::test(Index::class)->call('confirmDelete', $crud->id)->call('delete');

    expect(Schema::hasTable('crud_testimonials'))->toBeFalse();
});

it('removes and reorders existing fields on edit', function () {
    makeCrud();
    $crud = Crud::first();

    Livewire::test(Form::class, ['id' => $crud->id])
        ->call('moveField', 2, -1)
        ->call('removeField', 0)
        ->set('fields.0.label', 'Is featured')
        ->call('save')
        ->assertHasNoErrors();

    expect(Schema::hasColumn('crud_testimonials', 'customer_name'))->toBeFalse();
    expect(Crud::first()->fields)->toHaveCount(2);
    expect(collect(Crud::first()->fields)->pluck('name')->all())->toBe(['featured', 'rating']);
    expect(Crud::first()->fields[0]['label'])->toBe('Is featured');
});

it('ignores a forged column name on a new field', function () {
    makeCrud();
    $crud = Crud::first();

    Livewire::test(Form::class, ['id' => $crud->id])
        ->call('addField')
        ->set('fields.3.name', 'id')
        ->set('fields.3.label', 'Sneaky')
        ->call('save')
        ->assertHasNoErrors();

    expect(collect(Crud::first()->fields)->pluck('name')->all())->toContain('sneaky')->not->toContain('id');
});

it('sorts, filters, searches and bulk deletes records', function () {
    makeCrud();
    DB::table('crud_testimonials')->insert([
        ['customer_name' => 'Alpha', 'rating' => '1', 'featured' => true],
        ['customer_name' => 'Beta', 'rating' => '3', 'featured' => false],
        ['customer_name' => 'Gamma', 'rating' => '3', 'featured' => true],
    ]);

    Livewire::test(Records::class, ['slug' => 'testimonials'])
        ->call('sort', 'customer_name')
        ->assertSeeInOrder(['Alpha', 'Beta', 'Gamma'])
        ->call('sort', 'customer_name')
        ->assertSeeInOrder(['Gamma', 'Beta', 'Alpha'])
        ->set('filters.rating', '3')
        ->assertDontSee('Alpha')
        ->set('filters.featured', '1')
        ->assertSee('Gamma')
        ->assertDontSee('Beta')
        ->call('clearFilters')
        ->set('search', 'Bet')
        ->assertSee('Beta')
        ->assertDontSee('Gamma')
        ->call('clearFilters')
        ->set('selectedIds', [1, 2])
        ->call('bulkDelete');

    expect(DB::table('crud_testimonials')->pluck('customer_name')->all())->toBe(['Gamma']);
});

it('stores an uploaded image and cleans it up on delete', function () {
    Storage::fake('public');

    Livewire::test(Form::class)
        ->set('name', 'Gallery')
        ->set('fields', [['label' => 'Photo', 'type' => 'image', 'required' => true, 'in_list' => true, 'options' => '']])
        ->call('save');

    $component = Livewire::test(Records::class, ['slug' => 'gallery'])
        ->call('create')
        ->call('save')
        ->assertHasErrors(['form.photo'])
        ->set('uploads.photo', UploadedFile::fake()->image('a.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $path = DB::table('crud_gallery')->value('photo');
    Storage::disk('public')->assertExists($path);

    $component->call('confirmDelete', 1)->call('delete');
    Storage::disk('public')->assertMissing($path);
});

it('exports records as csv with formulas neutralised', function () {
    makeCrud();
    DB::table('crud_testimonials')->insert(['customer_name' => '=HYPERLINK("x")', 'rating' => '1', 'featured' => true]);

    $csv = $this->get(route('admin.cruds.export', 'testimonials'))->assertOk()->streamedContent();

    expect($csv)->toContain("'=HYPERLINK")->toContain('Customer name');
});
