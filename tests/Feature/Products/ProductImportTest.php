<?php

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVendor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Builds a CSV the way the Products screen's export writes one. Rows are keyed
 * by header name so a test only spells out the columns it cares about.
 *
 * @param  list<array<string, string|int>>  $rows
 * @param  list<string>|null  $header
 */
function productImportCsv(array $rows, ?array $header = null): string
{
    $header ??= [
        'ID', 'Name (EN)', 'Name (BN)', 'Slug', 'Category', 'Price', 'Status',
        'Featured', 'Sort Order', 'Created At', 'SKU', 'Quantity', 'Discount Price',
        'Product Type', 'Charge Shipping', 'Upcoming', 'Brand', 'Vendor',
        'Excerpt (EN)', 'Excerpt (BN)', 'Description (EN)', 'Description (BN)',
    ];

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $header);

    foreach ($rows as $row) {
        fputcsv($handle, array_map(fn (string $column) => (string) ($row[$column] ?? ''), $header));
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv;
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->import = fn (string $csv, bool $updateExisting = false) => Livewire::test(ProductsIndex::class)
        ->set('importFile', UploadedFile::fake()->createWithContent('products.csv', $csv))
        ->set('importUpdateExisting', $updateExisting)
        ->call('importCsv');
});

it('imports a new product with all exported fields', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Electronics', 'bn' => 'ইলেকট্রনিক্স']]);
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);
    $vendor = ProductVendor::factory()->create(['name' => 'Acme Supplies']);

    $component = ($this->import)(productImportCsv([[
        'Name (EN)' => 'Laptop',
        'Name (BN)' => 'ল্যাপটপ',
        'Slug' => 'laptop',
        'Category' => 'Electronics',
        'Price' => '1200.50',
        'Status' => 'Active',
        'Featured' => 'Yes',
        'Sort Order' => '5',
        'SKU' => 'LAP-1',
        'Quantity' => '7',
        'Discount Price' => '999.99',
        'Product Type' => 'digital',
        'Charge Shipping' => 'No',
        'Upcoming' => 'Yes',
        'Brand' => 'Acme',
        'Vendor' => 'Acme Supplies',
        'Excerpt (EN)' => 'Short summary',
        'Excerpt (BN)' => 'সংক্ষিপ্ত সারাংশ',
        'Description (EN)' => 'Full description',
        'Description (BN)' => 'বিস্তারিত বর্ণনা',
    ]]));

    $product = Product::sole();

    expect($component->get('importResult'))->toMatchArray([
        'created' => 1,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'warnings' => [],
    ])
        ->and($product->getTranslation('name', 'en'))->toBe('Laptop')
        ->and($product->getTranslation('name', 'bn'))->toBe('ল্যাপটপ')
        ->and((float) $product->price)->toBe(1200.50)
        ->and((float) $product->discount_price)->toBe(999.99)
        ->and($product->quantity)->toBe(7)
        ->and($product->sku)->toBe('LAP-1')
        ->and($product->product_type)->toBe('digital')
        ->and($product->is_featured)->toBeTrue()
        ->and($product->is_upcoming)->toBeTrue()
        ->and($product->charge_shipping)->toBeFalse()
        ->and($product->sort_order)->toBe(5)
        ->and($product->status)->toBe('active')
        ->and($product->brand_id)->toBe($brand->id)
        ->and($product->vendor_id)->toBe($vendor->id)
        ->and($product->created_by)->toBe($this->admin->id)
        ->and($product->getTranslation('excerpt', 'en'))->toBe('Short summary')
        ->and($product->getTranslation('description', 'bn'))->toBe('বিস্তারিত বর্ণনা')
        ->and($product->categories->modelKeys())->toBe([$category->id]);

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->sole();

    expect($page->slug)->toBe('laptop')
        ->and($page->status)->toBe('active')
        ->and($page->getTranslation('title', 'en'))->toBe('Laptop');
});

it('keeps existing values when optional cells are blank on an update', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Hardware', 'bn' => '']]);
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);
    $vendor = ProductVendor::factory()->create();

    $product = Product::factory()->published()->create([
        'name' => ['en' => 'Old Name', 'bn' => 'পুরনো নাম'],
        'price' => 100,
        'quantity' => 25,
        'sku' => 'KEEP-1',
        'product_type' => 'digital',
        'is_featured' => true,
        'is_upcoming' => true,
        'charge_shipping' => false,
        'sort_order' => 3,
        'brand_id' => $brand->id,
        'vendor_id' => $vendor->id,
        'excerpt' => ['en' => 'Old excerpt', 'bn' => ''],
        'description' => ['en' => 'Old description', 'bn' => ''],
    ]);
    $product->categories()->attach($category);
    pairPageFor($product, 'product', 'old-name', $this->admin->id);

    // A rename sheet: ID and Name (EN), every other cell left blank.
    $component = ($this->import)(
        productImportCsv([[
            'ID' => $product->id,
            'Name (EN)' => 'Renamed Product',
        ]], ['ID', 'Name (EN)']),
        true,
    );

    $product->refresh();

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 1, 'skipped' => 0])
        ->and($product->getTranslation('name', 'en'))->toBe('Renamed Product')
        ->and((float) $product->price)->toBe(100.0)
        ->and($product->quantity)->toBe(25)
        ->and($product->sku)->toBe('KEEP-1')
        ->and($product->product_type)->toBe('digital')
        ->and($product->is_featured)->toBeTrue()
        ->and($product->is_upcoming)->toBeTrue()
        ->and($product->charge_shipping)->toBeFalse()
        ->and($product->sort_order)->toBe(3)
        ->and($product->brand_id)->toBe($brand->id)
        ->and($product->vendor_id)->toBe($vendor->id)
        ->and($product->status)->toBe('active')
        ->and($product->categories->modelKeys())->toBe([$category->id])
        ->and($product->getTranslation('excerpt', 'en'))->toBe('Old excerpt')
        ->and($product->getTranslation('description', 'en'))->toBe('Old description')
        ->and($product->slug)->toBe('old-name');
});

it('clears stock, discount and flags when the sheet says so', function () {
    $product = Product::factory()->create([
        'name' => ['en' => 'Clearable', 'bn' => ''],
        'quantity' => 10,
        'price' => 50,
        'discount_price' => 40,
        'is_featured' => true,
        'is_upcoming' => true,
        'charge_shipping' => true,
    ]);

    $component = ($this->import)(productImportCsv([[
        'ID' => $product->id,
        'Name (EN)' => 'Clearable',
        'Quantity' => '0',
        'Discount Price' => '',
        'Featured' => 'No',
        'Upcoming' => 'No',
        'Charge Shipping' => 'No',
    ]]), true);

    $product->refresh();

    expect($component->get('importResult')['updated'])->toBe(1)
        ->and($product->quantity)->toBe(0)
        ->and($product->discount_price)->toBeNull()
        ->and($product->is_featured)->toBeFalse()
        ->and($product->is_upcoming)->toBeFalse()
        ->and($product->charge_shipping)->toBeFalse();
});

it('finds an existing product by slug and by sku', function () {
    $bySlug = Product::factory()->create(['name' => ['en' => 'By Slug', 'bn' => ''], 'sku' => 'SLUG-1']);
    pairPageFor($bySlug, 'product', 'by-slug', $this->admin->id);

    $bySku = Product::factory()->create(['name' => ['en' => 'By Sku', 'bn' => ''], 'sku' => 'SKU-1']);
    pairPageFor($bySku, 'product', 'by-sku', $this->admin->id);

    $component = ($this->import)(productImportCsv([
        ['Name (EN)' => 'Slug Renamed', 'Slug' => 'by-slug'],
        ['Name (EN)' => 'Sku Renamed', 'SKU' => 'SKU-1'],
    ]), true);

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 2])
        ->and($bySlug->refresh()->getTranslation('name', 'en'))->toBe('Slug Renamed')
        ->and($bySku->refresh()->getTranslation('name', 'en'))->toBe('Sku Renamed');
});

it('skips existing products when the update toggle is off', function () {
    $product = Product::factory()->create(['name' => ['en' => 'Keep Me', 'bn' => ''], 'sku' => 'KEEP-1']);

    $component = ($this->import)(productImportCsv([[
        'ID' => $product->id,
        'Name (EN)' => 'Should Not Replace',
    ]]));

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 0, 'skipped' => 1])
        ->and($product->refresh()->getTranslation('name', 'en'))->toBe('Keep Me');
});

it('rejects reusing another existing product\'s SKU', function () {
    $other = Product::factory()->create(['name' => ['en' => 'Other Product', 'bn' => ''], 'sku' => 'TAKEN-1']);
    $product = Product::factory()->create(['name' => ['en' => 'Second Product', 'bn' => ''], 'sku' => 'OWN-1']);
    pairPageFor($product, 'product', 'second-product', $this->admin->id);

    $component = ($this->import)(productImportCsv([[
        'ID' => $product->id,
        'Name (EN)' => 'Second Product',
        'SKU' => 'TAKEN-1',
    ]]), true);

    $result = $component->get('importResult');

    expect($result['created'])->toBe(0)
        ->and($result['updated'])->toBe(0)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'][0])->toContain('SKU "TAKEN-1" is already used by another product.')
        ->and(Product::count())->toBe(2);
});

it('warns about unknown categories, brands and vendors but still imports the product', function () {
    $component = ($this->import)(productImportCsv([[
        'Name (EN)' => 'Mystery Product',
        'Category' => 'Missing Category, Another Missing',
        'Brand' => 'Missing Brand',
        'Vendor' => 'Missing Vendor',
    ]]));

    $result = $component->get('importResult');
    $product = Product::sole();

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBe([])
        ->and($result['warnings'])->toHaveCount(4)
        ->and($product->brand_id)->toBeNull()
        ->and($product->vendor_id)->toBeNull()
        ->and($product->categories)->toBeEmpty();
});

it('rejects a non-numeric price as a row error', function () {
    $component = ($this->import)(productImportCsv([
        ['Name (EN)' => 'Bad Price', 'Price' => 'not-a-number'],
        ['Name (EN)' => 'Good Price', 'Price' => '10'],
    ]));

    $result = $component->get('importResult');

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'][0])->toContain('"price" must be a number: not-a-number');
});

it('warns and clears a discount price that is not below the regular price', function () {
    $component = ($this->import)(productImportCsv([[
        'Name (EN)' => 'No Real Discount',
        'Price' => '100',
        'Discount Price' => '100',
    ]]));

    $result = $component->get('importResult');
    $product = Product::sole();

    expect($result['created'])->toBe(1)
        ->and($result['warnings'][0])->toContain('Discount Price was not lower than Price')
        ->and($product->discount_price)->toBeNull();
});

it('appends a suffix when an imported product slug is already taken', function () {
    Page::factory()->create(['type' => 'product', 'slug' => 'gadget', 'status' => 'active']);

    ($this->import)(productImportCsv([[
        'Name (EN)' => 'Gadget',
        'Slug' => 'gadget',
    ]]));

    expect(Product::sole()->slug)->toBe('gadget-2');
});

it('rejects a file whose header lacks Name (EN)', function () {
    $component = ($this->import)(productImportCsv(
        [['Name (BN)' => 'নাম']],
        ['ID', 'Name (BN)'],
    ));

    expect($component->get('importResult'))->toBeNull()
        ->and(Product::count())->toBe(0);

    $component->assertDispatched('notify', fn ($event, $params) => ($params['type'] ?? null) === 'error'
        && str_contains($params['message'] ?? '', 'Name (EN)'));
});

it('accepts a header with a UTF-8 BOM and different casing', function () {
    $csv = "\xEF\xBB\xBFname (en),price\nBom Product,12.50\n";

    $component = ($this->import)($csv);

    expect($component->get('importResult')['created'])->toBe(1)
        ->and(Product::sole()->getTranslation('name', 'en'))->toBe('Bom Product')
        ->and((float) Product::sole()->price)->toBe(12.5);
});

it('requires a CSV file before importing', function () {
    Livewire::test(ProductsIndex::class)
        ->call('importCsv')
        ->assertHasErrors(['importFile']);
});
