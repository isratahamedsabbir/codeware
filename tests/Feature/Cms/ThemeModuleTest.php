<?php

use App\Support\Themes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/*
|--------------------------------------------------------------------------
| Themes as modules
|--------------------------------------------------------------------------
|
| A theme is one folder, themes/{slug}/, holding its routes, templates,
| controllers, database files and public assets — the same shape as a plugin.
| ThemeServiceProvider is the only thing that plugs one in.
|
*/

it('keeps every part of a bundled theme inside its own folder', function (string $slug) {
    $folder = base_path("themes/{$slug}");

    expect(is_file("{$folder}/theme.json"))->toBeTrue()
        ->and(is_file("{$folder}/routes.php"))->toBeTrue()
        ->and(is_file("{$folder}/theme.css"))->toBeTrue()
        ->and(is_dir("{$folder}/Controllers"))->toBeTrue();
})->with(['default', 'ecommerce', 'portfolio']);

it('autoloads a theme controller from its own folder', function () {
    expect(class_exists('Themes\Ecommerce\Controllers\ShopController'))->toBeTrue()
        ->and(class_exists('Themes\Portfolio\Controllers\HomeController'))->toBeTrue()
        ->and(class_exists('Themes\Nope\Controllers\HomeController'))->toBeFalse();
});

it('autoloads a theme seeder from its database folder', function () {
    expect(class_exists('Themes\Portfolio\Database\Seeders\MenuSeeder'))->toBeTrue()
        ->and(class_exists('Themes\Ecommerce\Database\Seeders\MenuSeeder'))->toBeTrue();
});

it('registers each theme templates under its own view namespace', function (string $slug) {
    expect(View::exists(Themes::viewNamespace($slug).'::home'))->toBeTrue();
})->with(['default', 'ecommerce', 'portfolio']);

it('routes the storefront to controllers that live in the theme folder', function () {
    $action = Route::getRoutes()->getByName('shop')->getActionName();

    expect($action)->toStartWith('Themes\Ecommerce\Controllers\ShopController');
});

it('serves a file out of a theme assets folder', function () {
    $this->get('/themes/portfolio/style.css')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/css; charset=utf-8');
});

it('serves nothing from a theme outside its assets folder', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    'the manifest' => '/themes/portfolio/theme.json',
    'a template' => '/themes/portfolio/home.blade.php',
    'a traversal' => '/themes/portfolio/..%2Ftheme.json',
    'an unknown theme' => '/themes/nope/style.css',
]);

it('leaves nothing of a theme behind in the old locations', function () {
    expect(is_dir(resource_path('views/frontend/themes')))->toBeFalse()
        ->and(is_dir(resource_path('css/themes')))->toBeFalse()
        ->and(is_dir(base_path('routes/web')))->toBeFalse()
        ->and(is_dir(public_path('themes')))->toBeFalse()
        ->and(File::isDirectory(app_path('Http/Controllers/Themes/Ecommerce')))->toBeFalse();
});
