<?php

use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders the Additional Data section above the variations', function () {
    Livewire::test(ProductForm::class)
        ->assertSee('Additional Data')
        ->assertSee('Rich-text description, short description and specification for this product.');
});

it('hides the Additional Data section when the setting is off', function () {
    Setting::set('additional_data_products_enabled', '0');
    Cache::flush();

    Livewire::test(ProductForm::class)
        ->assertDontSee('Additional Data');
});

it('binds the Additional Data editors to the translatable fields', function () {
    $component = Livewire::test(ProductForm::class)->instance();

    expect($component->description)->toBe([])
        ->and($component->excerpt)->toBe([])
        ->and($component->specifications)->toBe([]);
});

it('persists description, short description and specifications on save', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Detailed Product')
        ->set('price', '1200')
        ->set('description.en', '<p>Full rich description</p>')
        ->set('excerpt.en', '<p>Short blurb</p>')
        ->set('specifications.en', '<ul><li>4K display</li><li>64GB storage</li></ul>')
        ->call('save');

    $product = Product::whereJsonContains('name->en', 'Detailed Product')->firstOrFail();

    expect($product->getTranslations('description'))->toBe(['en' => '<p>Full rich description</p>'])
        ->and($product->getTranslations('excerpt'))->toBe(['en' => '<p>Short blurb</p>'])
        ->and($product->getTranslations('specifications'))->toBe(['en' => '<ul><li>4K display</li><li>64GB storage</li></ul>']);
});

it('persists each locale independently', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Bilingual Product')
        ->set('price', '800')
        ->set('description.en', '<p>English description</p>')
        ->set('description.bn', '<p>বাংলা বর্ণনা</p>')
        ->set('excerpt.en', '<p>En short</p>')
        ->set('excerpt.bn', '<p>বাংলা সংক্ষিপ্ত</p>')
        ->call('save');

    $product = Product::whereJsonContains('name->en', 'Bilingual Product')->firstOrFail();

    expect($product->getTranslation('description', 'en'))->toBe('<p>English description</p>')
        ->and($product->getTranslation('description', 'bn'))->toBe('<p>বাংলা বর্ণনা</p>')
        ->and($product->getTranslation('excerpt', 'bn'))->toBe('<p>বাংলা সংক্ষিপ্ত</p>');
});

it('hydrates the details fields back into the edit form', function () {
    $product = Product::factory()->create([
        'name' => ['en' => 'Editable Product', 'bn' => ''],
        'description' => ['en' => '<p>Existing description</p>', 'bn' => ''],
        'excerpt' => ['en' => '<p>Existing blurb</p>', 'bn' => ''],
        'specifications' => ['en' => '<p>Existing specs</p>', 'bn' => ''],
    ]);
    pairPageFor($product, 'product', 'editable-product', $this->admin->id);

    $component = Livewire::test(ProductForm::class, ['id' => $product->id])
        ->assertSet('description.en', '<p>Existing description</p>')
        ->assertSet('excerpt.en', '<p>Existing blurb</p>')
        ->assertSet('specifications.en', '<p>Existing specs</p>');
});
