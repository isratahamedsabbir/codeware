<?php

namespace App\Providers;

use App\Http\Controllers\ThemeAssetController;
use App\Support\Themes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires every installed theme (see App\Support\Themes) into the app, the same way
 * PluginServiceProvider does for plugins: a theme is a self-contained module in
 * themes/{slug}/ and this is the only place that knows how to plug one in.
 *
 *  - its classes (Controllers/, database/seeders/) autoload from the folder
 *    under Themes\{Slug}\..., so a theme installed from a zip needs no composer dump;
 *  - its templates are the theme-{slug}:: view namespace;
 *  - its database/migrations are loaded with the app's own;
 *  - its public/ folder is served at /themes/{slug}/...;
 *  - its routes/web.php is registered by routes/web.php (behind the 'theme' guard,
 *    so only the active theme answers).
 */
class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        spl_autoload_register([Themes::class, 'autoload']);
    }

    public function boot(): void
    {
        if (! is_dir(Themes::path())) {
            return;
        }

        try {
            $slugs = array_keys(Themes::all());
        } catch (\Throwable) {
            return;
        }

        foreach ($slugs as $slug) {
            Themes::registerViews($slug);

            $migrations = Themes::databasePath($slug).'/migrations';

            if (is_dir($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }
        }

        if (! $this->app->routesAreCached()) {
            Route::get('themes/{theme}/{path}', ThemeAssetController::class)
                ->where('theme', '[A-Za-z0-9_-]+')
                ->where('path', '.+')
                ->name('theme.asset');
        }
    }
}
