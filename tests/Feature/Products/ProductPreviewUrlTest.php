<?php

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Product;
use App\Models\User;
use App\Support\EnvFile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — point it at a
    // throwaway file instead, and always restore the override afterwards.
    $this->envPath = sys_get_temp_dir().'/product-preview-url-test-'.uniqid().'.env';

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

it('links a product straight to the frontend url and slug when no preview path is set', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_product_path' => '']);
    pairPageFor(Product::factory()->create(), 'product', 'wireless-mouse', $this->admin->id);

    $html = Livewire::test(ProductsIndex::class)->html();

    expect($html)->toContain('https://frontend.example.test/wireless-mouse');
});

it('inserts the configured preview path between the frontend url and the slug', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_product_path' => 'product']);
    pairPageFor(Product::factory()->create(), 'product', 'wireless-mouse', $this->admin->id);

    $html = Livewire::test(ProductsIndex::class)->html();

    expect($html)->toContain('https://frontend.example.test/product/wireless-mouse');
});

it('saves the product preview path from the settings modal', function () {
    Livewire::test(ProductsIndex::class)
        ->set('productPreviewPath', 'product')
        ->call('saveFrontendUrl');

    expect(EnvFile::get('FRONTEND_PRODUCT_PATH'))->toBe('product');
});

it('strips leading and trailing slashes from the saved preview path', function () {
    Livewire::test(ProductsIndex::class)
        ->set('productPreviewPath', '/product/')
        ->call('saveFrontendUrl');

    expect(EnvFile::get('FRONTEND_PRODUCT_PATH'))->toBe('product');
});
