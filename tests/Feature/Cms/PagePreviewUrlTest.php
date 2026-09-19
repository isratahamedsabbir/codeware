<?php

use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Support\EnvFile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — point it at a
    // throwaway file instead, and always restore the override afterwards.
    $this->envPath = sys_get_temp_dir().'/page-preview-url-test-'.uniqid().'.env';

    file_put_contents($this->envPath, <<<'ENV'
        APP_URL=https://example.test
        FRONTEND_URL=https://frontend.example.test
        APP_KEY=base64:untouchedsecretkeyvalue==
        ENV);

    EnvFile::$pathOverride = $this->envPath;

    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    EnvFile::$pathOverride = null;
    @unlink($this->envPath);
    Artisan::call('config:clear');
});

it('links a standalone page straight to the frontend url and slug when no preview path is set', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_page_path' => '']);
    Page::factory()->create(['type' => 'page', 'slug' => 'about-us']);

    $html = Livewire::test(PagesIndex::class)->html();

    expect($html)->toContain('https://frontend.example.test/about-us');
});

it('inserts the configured preview path between the frontend url and a page slug', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_page_path' => 'pages']);
    Page::factory()->create(['type' => 'page', 'slug' => 'about-us']);

    $html = Livewire::test(PagesIndex::class)->html();

    expect($html)->toContain('https://frontend.example.test/pages/about-us');
});

it('shows a working preview link for a non-standalone page row, using that type\'s own frontend path', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_product_path' => '']);
    $product = Product::factory()->create();
    pairPageFor($product, 'product', 'wireless-mouse', $this->admin->id);

    $html = Livewire::test(PagesIndex::class)->set('typeFilter', 'product')->html();

    expect($html)->toContain('https://frontend.example.test/wireless-mouse');
});

it('inserts the configured product preview path for a product page row', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_product_path' => 'shop']);
    $product = Product::factory()->create();
    pairPageFor($product, 'product', 'wireless-mouse', $this->admin->id);

    $html = Livewire::test(PagesIndex::class)->set('typeFilter', 'product')->html();

    expect($html)->toContain('https://frontend.example.test/shop/wireless-mouse');
});

it('saves the page preview path from the settings modal', function () {
    Livewire::test(PagesIndex::class)
        ->set('pagePreviewPath', 'pages')
        ->call('saveFrontendUrl');

    expect(EnvFile::get('FRONTEND_PAGE_PATH'))->toBe('pages');
});

it('strips leading and trailing slashes from the saved page preview path', function () {
    Livewire::test(PagesIndex::class)
        ->set('pagePreviewPath', '/pages/')
        ->call('saveFrontendUrl');

    expect(EnvFile::get('FRONTEND_PAGE_PATH'))->toBe('pages');
});
