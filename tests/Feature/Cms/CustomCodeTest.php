<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    Setting::set('custom_head_code', '<meta name="custom-head-probe" content="1">');
    Setting::set('custom_body_code', '<script>window.customBodyProbe = 1;</script>');
});

it('injects the custom head and body code on every theme\'s public pages', function (string $theme) {
    Setting::set('site_theme', $theme);

    $html = $this->get('/')->assertOk()->getContent();

    // Head code lands inside <head>, body code just before </body>.
    expect(strpos($html, 'custom-head-probe'))->toBeLessThan(strpos($html, '</head>'))
        ->and($html)->toContain('<script>window.customBodyProbe = 1;</script>')
        ->and(strpos($html, 'customBodyProbe'))->toBeLessThan(strrpos($html, '</body>'))
        ->and(strpos($html, 'customBodyProbe'))->toBeGreaterThan(strpos($html, '</head>'));
})->with(['ecommerce', 'portfolio', 'default']);

it('injects the custom code on inner ecommerce pages too', function () {
    Setting::set('site_theme', 'ecommerce');

    $this->get('/shop')->assertOk()
        ->assertSee('custom-head-probe', false)
        ->assertSee('customBodyProbe', false);
});

it('injects the custom code on the storefront login page, like any other public page', function () {
    // A theme's own auth/login template, so a public page of the active theme —
    // which is where the code used to be missing, because that page used to be
    // the shared admin-styled one rather than the theme's.
    Setting::set('site_theme', 'ecommerce');

    $this->get(route('login'))->assertOk()
        ->assertSee('custom-head-probe', false)
        ->assertSee('customBodyProbe', false);
});

it('keeps the custom code off the admin panel', function () {
    Setting::set('site_theme', 'ecommerce');
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    $this->get(config('app.admin_url').'/developer-tools')->assertOk()
        ->assertDontSee('custom-head-probe', false)
        ->assertDontSee('customBodyProbe', false);
});

it('outputs nothing when no custom code is set', function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('custom_head_code', '');
    Setting::set('custom_body_code', '');

    $this->get('/')->assertOk()
        ->assertDontSee('custom-head-probe', false)
        ->assertDontSee('customBodyProbe', false);
});

it('outputs a snippet only on the pages that picked it', function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('custom_code_rules', json_encode([
        ['id' => 'abc123', 'name' => 'About pixel', 'head' => '<meta name="snippet-probe" content="1">', 'body' => '<script>window.snippetProbe = 1;</script>'],
    ]));
    Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'custom_code_id' => 'abc123']);
    Page::factory()->published()->create(['title' => ['en' => 'Contact', 'bn' => ''], 'slug' => 'contact']);

    $this->get('/about')->assertOk()
        ->assertSee('snippet-probe', false)
        ->assertSee('snippetProbe', false)
        ->assertSee('custom-head-probe', false);

    $this->get('/contact')->assertOk()
        ->assertDontSee('snippet-probe', false)
        ->assertDontSee('snippetProbe', false)
        ->assertSee('custom-head-probe', false);
});

it('saves named custom code snippets from the settings screen', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    Livewire\Livewire::test(App\Livewire\Admin\Settings\Index::class)
        ->call('addCodeRule')
        ->set('codeRules.0.name', 'Product pixel')
        ->set('codeRules.0.head', '<meta name="p" content="1">')
        ->call('save')
        ->assertHasNoErrors();

    $rules = App\Support\CustomCode::rules();

    expect($rules)->toHaveCount(1)
        ->and($rules[0])->toMatchArray(['name' => 'Product pixel', 'head' => '<meta name="p" content="1">', 'body' => ''])
        ->and($rules[0]['id'])->not->toBe('');
});

it('stores the picked snippet on the page from the page form', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    Setting::set('custom_code_rules', json_encode([
        ['id' => 'abc123', 'name' => 'About pixel', 'head' => 'x', 'body' => ''],
    ]));
    $page = Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about']);

    Livewire\Livewire::test(App\Livewire\Admin\Pages\Form::class, ['id' => $page->id])
        ->set('custom_code_id', 'abc123')
        ->call('save')
        ->assertHasNoErrors();

    expect($page->fresh()->custom_code_id)->toBe('abc123');
});

it('offers the snippets in a Custom Code card on the page, product, post and category forms', function (string $form) {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    Setting::set('custom_code_rules', json_encode([
        ['id' => 'abc123', 'name' => 'Checkout pixel', 'head' => 'x', 'body' => ''],
    ]));

    Livewire\Livewire::test($form)
        ->assertSee('Custom Code')
        ->assertSee('Checkout pixel');
})->with([
    App\Livewire\Admin\Pages\Form::class,
    App\Livewire\Admin\Products\Form::class,
    App\Livewire\Admin\Posts\Form::class,
    App\Livewire\Admin\Categories\Form::class,
]);

describe('page-type coverage', function () {
    beforeEach(function () {
        Setting::set('site_theme', 'ecommerce');
        Setting::set('custom_head_code', '<meta name="g-head" content="1">');
        Setting::set('custom_code_rules', json_encode([
            ['id' => 'aaa', 'name' => 'A', 'head' => '<meta name="snip-a" content="1">', 'body' => '<i id="body-a"></i>'],
            ['id' => 'bbb', 'name' => 'B', 'head' => '<meta name="snip-b" content="1">', 'body' => ''],
        ]));
    });

    it('loads the picked snippet on a product page', function () {
        $p = Product::factory()->published()->create();
        Page::factory()->published()->create(['type' => 'product', 'product_id' => $p->id, 'slug' => 'prod-x', 'custom_code_id' => 'aaa']);
        $this->get('/products/prod-x')->assertOk()->assertSee('g-head', false)->assertSee('snip-a', false)->assertSee('body-a', false)->assertDontSee('snip-b', false);
    });

    it('loads the picked snippet on a blog post', function () {
        $p = Post::factory()->published()->create();
        Page::factory()->published()->create(['type' => 'post', 'post_id' => $p->id, 'slug' => 'post-x', 'custom_code_id' => 'bbb']);
        $this->get('/blog/post-x')->assertOk()->assertSee('snip-b', false)->assertDontSee('snip-a', false);
    });

    it('loads the picked snippet on a product category page', function () {
        $c = ProductCategory::factory()->create(['status' => 'active']);
        Page::factory()->published()->create(['type' => 'product_category', 'category_id' => $c->id, 'slug' => 'cat-x', 'custom_code_id' => 'aaa']);
        $this->get('/category/cat-x')->assertOk()->assertSee('snip-a', false);
    });

    it('loads the picked snippet on the homepage', function () {
        Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'custom_code_id' => 'bbb']);
        $this->get('/')->assertOk()->assertSee('snip-b', false);
    });

    it('falls back to the global code when the picked snippet no longer exists', function () {
        Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'custom_code_id' => 'gone']);
        $this->get('/about')->assertOk()->assertSee('g-head', false)->assertDontSee('snip-', false);
    });

    it('saves and reloads the picked snippet from the product form', function () {
        $this->seed(Database\Seeders\RolePermissionSeeder::class);
        $this->actingAs(App\Models\User::factory()->admin()->create());
        $product = Product::factory()->create();
        Page::create(['user_id' => $product->user_id ?? App\Models\User::first()->id, 'type' => 'product', 'product_id' => $product->id, 'slug' => 'pf', 'title' => ['en' => 'T', 'bn' => ''], 'status' => 'active']);

        Livewire\Livewire::test(App\Livewire\Admin\Products\Form::class, ['id' => $product->id])
            ->set('custom_code_id', 'bbb')
            ->call('save')
            ->assertHasNoErrors();
        expect(Page::where('product_id', $product->id)->value('custom_code_id'))->toBe('bbb');

        Livewire\Livewire::test(App\Livewire\Admin\Products\Form::class, ['id' => $product->id])
            ->assertSet('custom_code_id', 'bbb')
            ->set('custom_code_id', '')
            ->call('save');
        expect(Page::where('product_id', $product->id)->value('custom_code_id'))->toBeNull();
    });
});

it('exposes the picked snippet in the public page API meta_data', function () {
    Setting::set('custom_code_rules', json_encode([
        ['id' => 'aaa', 'name' => 'A', 'head' => '<meta name="snip-a" content="1">', 'body' => '<i></i>'],
    ]));
    Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'custom_code_id' => 'aaa']);
    Page::factory()->published()->create(['title' => ['en' => 'Contact', 'bn' => ''], 'slug' => 'contact']);

    $this->getJson('/api/v1/pages/about')->assertOk()
        ->assertJsonPath('data.meta_data.custom_code.id', 'aaa')
        ->assertJsonPath('data.meta_data.custom_code.head', '<meta name="snip-a" content="1">')
        ->assertJsonPath('data.meta_data.custom_code.body', '<i></i>');

    $this->getJson('/api/v1/pages/contact')->assertOk()
        ->assertJsonPath('data.meta_data.custom_code', null);
});

it('lets the admin API set and clear a page\'s snippet, rejecting unknown ids', function () {
    $this->seed(RolePermissionSeeder::class);
    Laravel\Sanctum\Sanctum::actingAs(User::factory()->admin()->create());
    Setting::set('custom_code_rules', json_encode([
        ['id' => 'aaa', 'name' => 'A', 'head' => 'x', 'body' => ''],
    ]));
    $page = Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about']);

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['custom_code_id' => 'nope'])->assertUnprocessable();
    $this->putJson("/api/v1/admin/pages/{$page->id}", ['custom_code_id' => 'aaa'])->assertOk();
    expect($page->fresh()->custom_code_id)->toBe('aaa');

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['custom_code_id' => null])->assertOk();
    expect($page->fresh()->custom_code_id)->toBeNull();
});
