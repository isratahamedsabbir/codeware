<?php

use App\Livewire\Admin\Advance\Backup;
use App\Livewire\Admin\Advance\Database;
use App\Livewire\Admin\Advance\Robots;
use App\Livewire\Admin\Advance\Sitemap;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->sitemapPath = public_path('sitemap.xml');
    $this->robotsPath = public_path('robots.txt');
    $this->robotsBackup = File::exists($this->robotsPath) ? File::get($this->robotsPath) : null;
    $this->sitemapBackup = File::exists($this->sitemapPath) ? File::get($this->sitemapPath) : null;

    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->staff = User::factory()->create(['is_admin' => false]);
    $this->staff->assignRole('staff');
});

afterEach(function () {
    if ($this->robotsBackup !== null) {
        File::put($this->robotsPath, $this->robotsBackup);
    } elseif (File::exists($this->robotsPath)) {
        File::delete($this->robotsPath);
    }

    if ($this->sitemapBackup !== null) {
        File::put($this->sitemapPath, $this->sitemapBackup);
    } elseif (File::exists($this->sitemapPath)) {
        File::delete($this->sitemapPath);
    }
});

it('lets an admin view the sitemap and robots.txt tools', function () {
    $this->actingAs($this->admin);

    Livewire::test(Sitemap::class)->assertStatus(200);
    Livewire::test(Robots::class)->assertStatus(200);
});

it('lets an admin view the database and backup tools', function () {
    $this->actingAs($this->admin);

    Livewire::test(Database::class)->assertStatus(200);
    Livewire::test(Backup::class)->assertStatus(200);
});

it('blocks staff from the advance routes', function () {
    $this->actingAs($this->staff);

    $this->get(route('admin.advance.sitemap'))->assertForbidden();
    $this->get(route('admin.advance.robots'))->assertForbidden();
    $this->get(route('admin.advance.database'))->assertForbidden();
    $this->get(route('admin.advance.backup'))->assertForbidden();
});

it('generates a sitemap.xml from published content', function () {
    $this->actingAs($this->admin);

    Page::factory()->create(['type' => 'page', 'status' => 'active', 'no_index' => false, 'slug' => 'about-us']);
    $category = ProductCategory::factory()->create(['status' => 'active', 'slug' => 'gadgets']);
    Product::factory()->create(['product_category_id' => $category->id, 'status' => 'active', 'slug' => 'widget']);

    Livewire::test(Sitemap::class)->call('generate');

    expect(File::exists($this->sitemapPath))->toBeTrue();

    $xml = File::get($this->sitemapPath);
    expect($xml)->toContain('/about-us')
        ->toContain('/products/widget')
        ->toContain('/products/category/gadgets');
});

it('saves robots.txt content through the form', function () {
    $this->actingAs($this->admin);

    Livewire::test(Robots::class)
        ->set('content', "User-agent: *\nDisallow: /admin\n")
        ->call('save');

    expect(File::get($this->robotsPath))->toBe("User-agent: *\nDisallow: /admin\n");
});

it('downloads a sql dump of the database', function () {
    $this->actingAs($this->admin);

    Livewire::test(Database::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('downloads a zip of the storage directory', function () {
    $this->actingAs($this->admin);

    Livewire::test(Backup::class)
        ->call('download')
        ->assertFileDownloaded();
});
