<?php

namespace App\Providers;

use App\Support\Plugins;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Wires every active plugin (see App\Support\Plugins) into the app: its views as
 * the plugin-{slug}:: namespace, its migrations, and its optional routes.php,
 * mounted on the admin host under /plugins/{slug}/ and admin.plugins.{slug}.*
 * behind the same auth + system-admin gates as the rest of the panel.
 */
class PluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! is_dir(Plugins::path())) {
            return;
        }

        // "Active" lives in the settings table, which may not exist yet on a
        // fresh checkout before the first migrate.
        try {
            $plugins = Plugins::all();
        } catch (\Throwable) {
            return;
        }

        foreach ($plugins as $slug => $plugin) {
            View::addNamespace(Plugins::viewNamespace($slug), $plugin['path']);

            if (! $plugin['active']) {
                continue;
            }

            if (is_dir($plugin['path'].'/migrations')) {
                $this->loadMigrationsFrom($plugin['path'].'/migrations');
            }

            if (is_file($plugin['path'].'/routes.php') && ! $this->app->routesAreCached()) {
                Route::middleware(['web', 'auth', 'admin', 'activity-log', 'can:access-admin-system'])
                    ->domain(config('app.admin_host'))
                    ->prefix("plugins/{$slug}")
                    ->name("admin.plugins.{$slug}.")
                    ->group($plugin['path'].'/routes.php');
            }
        }
    }
}
