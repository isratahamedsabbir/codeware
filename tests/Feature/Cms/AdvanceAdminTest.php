<?php

use App\Livewire\Admin\Advance\Backup;
use App\Livewire\Admin\Advance\Robots;
use App\Livewire\Admin\Advance\Sitemap;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo\SeoResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    // The ecommerce theme, because the sitemap only advertises what the active
    // theme can actually serve — under the default theme /products/widget is a
    // 404 and is correctly absent.
    Setting::set('site_theme', 'ecommerce');
    SeoResolver::flush();

    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->admin()->create();
    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
});

afterEach(function () {
    Cache::flush();
});

it('lets an admin view the sitemap and robots.txt tools', function () {
    $this->actingAs($this->admin);

    Livewire::test(Sitemap::class)->assertStatus(200);
    Livewire::test(Robots::class)->assertStatus(200);
});

it('lets an admin view the backup tool', function () {
    $this->actingAs($this->admin);

    Livewire::test(Backup::class)->assertStatus(200);
});

it('blocks staff from the advance routes', function () {
    $this->actingAs($this->staff);

    $this->get(route('admin.advance.sitemap'))->assertForbidden();
    $this->get(route('admin.advance.robots'))->assertForbidden();
    $this->get(route('admin.advance.backup'))->assertForbidden();
});

it('shows the sitemap an admin is looking at, built from published content', function () {
    $this->actingAs($this->admin);

    Page::factory()->create(['type' => 'page', 'status' => 'active', 'no_index' => false, 'slug' => 'about-us']);
    $category = ProductCategory::factory()->create(['status' => 'active']);
    pairPageFor($category, 'product_category', 'gadgets', $this->admin->id);
    $product = Product::factory()->create(['status' => 'active']);
    $product->categories()->attach($category);
    pairPageFor($product, 'product', 'widget', $this->admin->id);

    // There is no Generate step to invoke any more: the file this screen used to
    // write is now built per request, so the screen is a window onto the same
    // bytes a crawler gets rather than a button that has to be pressed.
    Livewire::test(Sitemap::class)
        ->assertOk()
        ->assertSee('about-us')
        ->assertSee('/products/widget')
        // /products/category/{slug} is not a route this application has; the old
        // sitemap listed it anyway, so a crawler was sent to a 404 by the site's
        // own inventory file.
        ->assertSee('/category/gadgets')
        ->assertDontSee('/products/category/gadgets');
});

it('saves robots.txt rules through the form, and serves them with the sitemap line', function () {
    $this->actingAs($this->admin);

    Livewire::test(Robots::class)
        ->set('content', "User-agent: *\nDisallow: /admin")
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('seo_robots_txt'))->toBe("User-agent: *\nDisallow: /admin");

    // The stored rules are what a crawler reads, plus the Sitemap: line the
    // controller appends — which no save can lose.
    expect($this->get('/robots.txt')->assertOk()->getContent())
        ->toContain("User-agent: *\nDisallow: /admin")
        ->toContain('Sitemap: '.url('/sitemap.xml'));
});

it('downloads a zip containing the database dump and the storage files', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(Backup::class)
        ->call('download')
        ->assertFileDownloaded();

    $download = $component->effects['download'];

    expect($download['name'])->toStartWith('full-backup-')->toEndWith('.zip');

    $zipPath = tempnam(sys_get_temp_dir(), 'backup').'.zip';
    file_put_contents($zipPath, base64_decode($download['content']));

    $zip = new ZipArchive;
    $zip->open($zipPath);

    $names = collect(range(0, $zip->numFiles - 1))->map(fn (int $i) => $zip->getNameIndex($i));

    expect($names->filter(fn (string $name) => str_starts_with($name, 'database/database-') && str_ends_with($name, '.sql')))
        ->toHaveCount(1);

    $zip->close();

    @unlink($zipPath);
});
