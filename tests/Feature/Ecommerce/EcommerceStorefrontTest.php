<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\FrontendMenuSeeder;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

function storefrontProduct(string $slug, array $attributes = []): Product
{
    $product = Product::factory()->published()->create($attributes + ['sort_order' => 0]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

it('leads the product page with its name and lists category, brand, type and tag values without labels', function () {
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Agroma', 'bn' => '']]);
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Herbal Tea', 'bn' => '']]);
    pairPageFor($category, 'product_category', 'herbal-tea', $this->admin->id);
    $tag = Tag::create(['name' => ['en' => 'Organic', 'bn' => ''], 'status' => 'active']);

    $product = storefrontProduct('spearmint-tea', ['name' => ['en' => 'Spearmint Tea', 'bn' => ''], 'brand_id' => $brand->id]);
    $product->categories()->attach($category);
    $product->tags()->attach($tag);

    $html = get('/products/spearmint-tea')->assertOk()->getContent();
    $main = substr($html, strpos($html, '<main'));

    // The name comes first; the value pills follow in order, with no labels.
    expect(strpos($main, 'Spearmint Tea</h1>'))->toBeLessThan(strpos($main, 'Herbal Tea'))
        ->and($main)->not->toContain('Categories:')
        ->and($main)->not->toContain('Brand:')
        ->and($main)->not->toContain('Type:')
        ->and($main)->not->toContain('Tags:');

    get('/products/spearmint-tea')->assertSeeInOrder([
        'Spearmint Tea</h1>', 'Herbal Tea', 'Agroma', 'Physical', '#Organic',
    ], false);
});

it('shows the stock status as a badge beside the product name and no shipping-charge note', function () {
    storefrontProduct('in-stock-tea', ['name' => ['en' => 'In Stock Tea', 'bn' => ''], 'quantity' => 5, 'charge_shipping' => true]);
    storefrontProduct('sold-out-tea', ['name' => ['en' => 'Sold Out Tea', 'bn' => ''], 'quantity' => 0, 'charge_shipping' => false]);

    get('/products/in-stock-tea')->assertOk()
        ->assertSeeInOrder(['In Stock Tea</h1>', 'In stock', 'Add to cart'], false)
        ->assertDontSee('Shipping charges apply')
        ->assertDontSee('Free shipping');

    get('/products/sold-out-tea')->assertOk()
        ->assertSeeInOrder(['Sold Out Tea</h1>', 'Out of stock'], false)
        ->assertSee('Free shipping');

    // Variant products: the badge and the price under the name start from the
    // default (first) combination and follow the picker via `variant-selected`.
    $variant = storefrontProduct('variant-tea', ['name' => ['en' => 'Variant Tea', 'bn' => ''], 'quantity' => 5, 'price' => 999]);
    $variant->update(['variations' => [
        ['attributes' => ['Weight' => '100 gm'], 'price' => 450, 'discount_price' => null, 'quantity' => 5, 'visible' => true, 'sku' => 'TEA-100'],
        ['attributes' => ['Weight' => '50 gm'], 'price' => 250, 'discount_price' => null, 'quantity' => 0, 'visible' => true],
    ]]);

    get('/products/variant-tea')->assertOk()
        ->assertSeeInOrder(['Variant Tea</h1>', 'In stock', 'TEA-100', format_money(450), '100 gm'], false)
        ->assertSee('variant-selected', false);
});

it('renders the additional data (excerpt, description, specifications) on the product page', function () {
    $product = storefrontProduct('rich-product', [
        'name' => ['en' => 'Rich Product', 'bn' => ''],
        'excerpt' => ['en' => '<p>Compact intro line</p>', 'bn' => ''],
        'description' => ['en' => '<h2>Full story</h2><p>Rich description body</p>', 'bn' => ''],
        'specifications' => ['en' => '<ul><li>4K display</li><li>64GB storage</li></ul>', 'bn' => ''],
    ]);

    $html = get('/products/rich-product')->assertOk()->getContent();

    // Rich HTML is rendered raw (Jodit output), not escaped.
    expect($html)->toContain('<h2>Full story</h2>')
        ->and($html)->toContain('<ul><li>4K display</li><li>64GB storage</li></ul>');

    // Description and Specifications are tabs; the active one is visible while
    // the other stays in the DOM behind x-cloak until it's clicked.
    expect($html)->toContain('role="tablist"')
        ->and($html)->toContain('tab === \'description\'')
        ->and($html)->toContain('tab === \'specifications\'');

    get('/products/rich-product')->assertSeeInOrder([
        'Compact intro line', 'Full story', '4K display',
    ], false);
});

it('defaults the product detail tabs to specifications when there is no description', function () {
    $product = storefrontProduct('specs-only-product', [
        'name' => ['en' => 'Specs Only Product', 'bn' => ''],
        'excerpt' => ['en' => '', 'bn' => ''],
        'description' => ['en' => '', 'bn' => ''],
        'specifications' => ['en' => '<ul><li>Only specs here</li></ul>', 'bn' => ''],
    ]);

    $html = get('/products/specs-only-product')->assertOk()->getContent();

    expect($html)->toContain("{ tab: 'specifications' }")
        ->and($html)->toContain('<li>Only specs here</li>')
        ->and($html)->not->toContain("tab = 'description'");
});

it('lists only active products on the shop page', function () {
    $visible = storefrontProduct('visible-product', ['name' => ['en' => 'Visible Product', 'bn' => '']]);
    $hidden = Product::factory()->draft()->create(['name' => ['en' => 'Hidden Product', 'bn' => '']]);
    pairPageFor($hidden, 'product', 'hidden-product', $this->admin->id);

    get('/shop')
        ->assertOk()
        ->assertSee('Visible Product')
        ->assertSee('/products/visible-product')
        ->assertDontSee('Hidden Product');
});

it('filters the shop by category', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    pairPageFor($category, 'product_category', 'fertilizers', $this->admin->id);

    $inCategory = storefrontProduct('in-category', ['name' => ['en' => 'In Category', 'bn' => '']]);
    $inCategory->categories()->attach($category);

    storefrontProduct('outside-category', ['name' => ['en' => 'Outside Category', 'bn' => '']]);

    get('/shop?category=fertilizers')
        ->assertOk()
        ->assertSee('In Category')
        ->assertDontSee('Outside Category');
});

it('filters the shop by brand', function () {
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme Supplies', 'bn' => '']]);

    $branded = storefrontProduct('branded', ['name' => ['en' => 'Branded Stuff', 'bn' => ''], 'brand_id' => $brand->id]);
    storefrontProduct('unbranded', ['name' => ['en' => 'Unbranded Stuff', 'bn' => ''], 'brand_id' => null]);

    get('/shop?brand=acme_supplies')
        ->assertOk()
        ->assertSee('Branded Stuff')
        ->assertDontSee('Unbranded Stuff');
});

it('filters the shop by tag', function () {
    $tag = Tag::create(['name' => ['en' => 'Organic', 'bn' => ''], 'status' => 'active']);

    $tagged = storefrontProduct('tagged', ['name' => ['en' => 'Tagged Product', 'bn' => '']]);
    $tagged->tags()->attach($tag);

    storefrontProduct('untagged', ['name' => ['en' => 'Untagged Product', 'bn' => '']]);

    get('/shop?tag=organic')
        ->assertOk()
        ->assertSee('Tagged Product')
        ->assertDontSee('Untagged Product');
});

it('searches the shop by name across the current and primary locale', function () {
    storefrontProduct('search-match', ['name' => ['en' => 'Unique Widget Deluxe', 'bn' => '']]);
    storefrontProduct('search-miss', ['name' => ['en' => 'Ordinary Gadget', 'bn' => '']]);

    get('/shop?search=Widget')
        ->assertOk()
        ->assertSee('Unique Widget Deluxe')
        ->assertDontSee('Ordinary Gadget');
});

it('sorts the shop by price', function () {
    $cheap = storefrontProduct('cheap', ['name' => ['en' => 'Cheap Thing', 'bn' => ''], 'price' => 10]);
    $expensive = storefrontProduct('expensive', ['name' => ['en' => 'Pricey Thing', 'bn' => ''], 'price' => 100]);

    get('/shop?sort=price_asc')
        ->assertOk()
        ->assertSeeInOrder(['Cheap Thing', 'Pricey Thing']);

    get('/shop?sort=price_desc')
        ->assertOk()
        ->assertSeeInOrder(['Pricey Thing', 'Cheap Thing']);
});

it('returns 404 for an unknown brand or tag filter on the shop', function () {
    get('/shop?brand=unknown_brand')->assertNotFound();
    get('/shop?tag=unknown_tag')->assertNotFound();
});

it('renders a full product detail page', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Tools', 'bn' => '']]);
    pairPageFor($category, 'product_category', 'tools', $this->admin->id);

    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);
    $tag = Tag::create(['name' => ['en' => 'Premium', 'bn' => ''], 'status' => 'active']);

    $product = storefrontProduct('acme-hammer', [
        'name' => ['en' => 'Acme Hammer', 'bn' => ''],
        'price' => 49.99,
        'discount_price' => 39.99,
        'sku' => 'HAMMER-1',
        'brand_id' => $brand->id,
    ]);

    $product->categories()->attach($category);
    $product->tags()->attach($tag);
    $product->faqs()->create(['question' => 'Is it durable?', 'answer' => 'Yes, very.', 'is_active' => true]);

    get('/products/acme-hammer')
        ->assertOk()
        ->assertSee('Acme Hammer')
        ->assertSee(format_money(39.99))
        ->assertSee('HAMMER-1')
        ->assertSee('Acme')
        ->assertSee('Tools')
        ->assertSee('Premium')
        ->assertSee('Is it durable?')
        ->assertSee('Yes, very.')
        ->assertSee('/brand/acme')
        ->assertSee('/category/tools')
        ->assertSee('/tag/premium');
});

it('renders the variation picker with grouped options on the product page', function () {
    $product = storefrontProduct('tshirt', [
        'name' => ['en' => 'Cotton T-Shirt', 'bn' => ''],
        'price' => 20,
    ]);

    $product->variations = [
        [
            'attributes' => ['Color' => 'Red', 'Size' => 'M'],
            'price' => 22, 'discount_price' => 18, 'quantity' => 5, 'visible' => true,
        ],
        [
            'attributes' => ['Color' => 'Blue', 'Size' => 'M'],
            'price' => 22, 'discount_price' => null, 'quantity' => 0, 'visible' => true,
        ],
    ];
    $product->save();

    get('/products/tshirt')
        ->assertOk()
        ->assertSee('Cotton T-Shirt')
        ->assertSee('Red')
        ->assertSee('Blue')
        ->assertSee('Size')
        ->assertSee('productVariants')
        ->assertSee('\u0022initial_selection\u0022:{\u0022Size\u0022:\u0022M\u0022,\u0022Color\u0022:\u0022Red\u0022}', false);
});

it('renders a category landing page with its products', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    pairPageFor($category, 'product_category', 'fertilizers', $this->admin->id);

    $inCategory = storefrontProduct('npk', ['name' => ['en' => 'NPK 20-20-20', 'bn' => '']]);
    $inCategory->categories()->attach($category);

    storefrontProduct('other', ['name' => ['en' => 'Other Product', 'bn' => '']]);

    get('/category/fertilizers')
        ->assertOk()
        ->assertSee('Fertilizers')
        ->assertSee('NPK 20-20-20')
        ->assertDontSee('Other Product');
});

it('links child categories on a parent category landing page', function () {
    $parent = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    pairPageFor($parent, 'product_category', 'fertilizers', $this->admin->id);

    $child = ProductCategory::factory()->create([
        'name' => ['en' => 'Organic', 'bn' => ''],
        'parent_id' => $parent->id,
        'status' => 'active',
    ]);
    pairPageFor($child, 'product_category', 'organic', $this->admin->id);

    get('/category/fertilizers')
        ->assertOk()
        ->assertSee('Organic')
        ->assertSee('/category/organic');
});

it('renders a brand landing page by slug derived from its name', function () {
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme Supplies', 'bn' => '']]);
    storefrontProduct('acme-item', ['name' => ['en' => 'Acme Item', 'bn' => ''], 'brand_id' => $brand->id]);

    get('/brand/acme_supplies')
        ->assertOk()
        ->assertSee('Acme Supplies')
        ->assertSee('Acme Item');

    get('/brand/not-a-brand')->assertNotFound();
});

it('renders a tag landing page by slug derived from its name', function () {
    $tag = Tag::create(['name' => ['en' => 'Organic', 'bn' => ''], 'status' => 'active']);
    $tagged = storefrontProduct('organic-seeds', ['name' => ['en' => 'Organic Seeds', 'bn' => '']]);
    $tagged->tags()->attach($tag);

    get('/tag/organic')
        ->assertOk()
        ->assertSee('Organic Seeds');

    get('/tag/not-a-tag')->assertNotFound();
});

it('shows featured products and categories on the ecommerce homepage', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Seeds', 'bn' => '']]);
    pairPageFor($category, 'product_category', 'seeds', $this->admin->id);

    $featured = storefrontProduct('featured-item', ['name' => ['en' => 'Featured Item', 'bn' => ''], 'is_featured' => true]);
    $featured->categories()->attach($category);

    Product::factory()->draft()->create(['name' => ['en' => 'Hidden Draft', 'bn' => '']]);

    get('/')
        ->assertOk()
        ->assertSee('Featured Item')
        ->assertSee('Seeds')
        ->assertDontSee('Hidden Draft');
});

it('shows brands without a logo on the ecommerce homepage', function () {
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme Supplies', 'bn' => ''], 'logo' => null]);
    storefrontProduct('acme-item', ['name' => ['en' => 'Acme Item', 'bn' => ''], 'brand_id' => $brand->id]);

    get('/')
        ->assertOk()
        ->assertSee('Shop by brand')
        ->assertSee('Acme Supplies')
        ->assertSee('/brand/acme-supplies');
});

it('prepends category and brand dropdowns to the ecommerce header menu', function () {
    $this->seed(FrontendMenuSeeder::class);

    $category = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    pairPageFor($category, 'product_category', 'fertilizers', $this->admin->id);

    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme Supplies', 'bn' => '']]);
    storefrontProduct('acme-item', ['name' => ['en' => 'Acme Item', 'bn' => ''], 'brand_id' => $brand->id]);

    get('/')
        ->assertOk()
        ->assertSee('Categories')
        ->assertSee('/category/fertilizers')
        ->assertSee('Brands')
        ->assertSee('/brand/acme-supplies')
        ->assertSeeInOrder(['Categories', 'Brands', 'Home']);
});

it('falls back to the ecommerce storefront templates when the active theme ships none', function () {
    Setting::set('site_theme', 'default');

    $product = storefrontProduct('fallback-product', ['name' => ['en' => 'Fallback Product', 'bn' => '']]);

    get('/shop')->assertOk()->assertSee('Shop');
    get('/products/fallback-product')->assertOk()->assertSee('Fallback Product');
    get('/category/unknown')->assertNotFound();
    get('/products/unknown')->assertNotFound();
});

it('filters the shop by a selected attribute combination value', function () {
    $red = storefrontProduct('attribute-red', ['name' => ['en' => 'Attribute Red Shirt', 'bn' => '']]);
    $red->variations = [
        ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'price' => 25, 'discount_price' => null, 'quantity' => 5, 'visible' => true],
    ];
    $red->save();

    $blue = storefrontProduct('attribute-blue', ['name' => ['en' => 'Attribute Blue Shirt', 'bn' => '']]);
    $blue->variations = [
        ['attributes' => ['Color' => 'Blue', 'Size' => 'M'], 'price' => 25, 'discount_price' => null, 'quantity' => 5, 'visible' => true],
    ];
    $blue->save();

    get('/shop?attributes[Color]=Red')
        ->assertOk()
        ->assertSee('Attribute Red Shirt')
        ->assertDontSee('Attribute Blue Shirt');
});

it('filters the shop by price range and product type', function () {
    storefrontProduct('range-cheap', ['name' => ['en' => 'Range Cheap', 'bn' => ''], 'price' => 10]);
    storefrontProduct('range-mid', ['name' => ['en' => 'Range Mid', 'bn' => ''], 'price' => 50]);
    storefrontProduct('range-expensive', ['name' => ['en' => 'Range Expensive', 'bn' => ''], 'price' => 100, 'product_type' => 'digital']);

    get('/shop?min_price=20&max_price=80')
        ->assertOk()
        ->assertSee('Range Mid')
        ->assertDontSee('Range Cheap')
        ->assertDontSee('Range Expensive');

    get('/shop?type=digital')
        ->assertOk()
        ->assertSee('Range Expensive')
        ->assertDontSee('Range Mid');
});

it('auto-applies the price range and lists every active filter as a removable chip', function () {
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Chip Brand', 'bn' => '']]);
    $tag = Tag::create(['name' => ['en' => 'Chiptag', 'bn' => ''], 'status' => 'active']);
    $product = storefrontProduct('chip-mid', ['name' => ['en' => 'Chip Mid', 'bn' => ''], 'price' => 50, 'brand_id' => $brand->id]);
    $product->tags()->attach($tag);
    storefrontProduct('chip-cheap', ['name' => ['en' => 'Chip Cheap', 'bn' => ''], 'price' => 10]);

    $html = get('/shop?min_price=20&max_price=80&brand=chip_brand&tag=chiptag&type=physical&search=Chip')
        ->assertOk()
        ->assertSee('Chip Mid')
        ->assertDontSee('Chip Cheap')
        // The price form submits itself — there's no Apply button any more.
        ->assertDontSee('Apply price')
        ->assertSee('requestSubmit()', false)
        ->assertSee('Clear all')
        ->getContent();

    // Removing the brand chip keeps every other filter in place.
    expect($html)->toContain('Chip Brand')
        ->and($html)->toContain('#Chiptag')
        ->and($html)->toContain('Search: Chip')
        ->and($html)->toContain(e(url('/shop?min_price=20&max_price=80&tag=chiptag&type=physical&search=Chip')));
});

it('renders attribute facets and price/type filters in the shop sidebar', function () {
    $product = storefrontProduct('facet-item', ['name' => ['en' => 'Facet Item', 'bn' => '']]);
    $product->variations = [
        ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'price' => 22, 'discount_price' => null, 'quantity' => 5, 'visible' => true],
    ];
    $product->save();

    get('/shop')
        ->assertOk()
        ->assertSee('Options')
        ->assertSee('Color')
        ->assertSee('Red')
        ->assertSee('Size')
        ->assertSee('Price')
        ->assertSee('Physical');
});
