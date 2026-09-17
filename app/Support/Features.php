<?php

namespace App\Support;

use App\Models\Feature;

/**
 * This admin panel is reused as a starting point across multiple projects, and not
 * every project needs every module (Chat, Blog, File Manager, ...). Each feature
 * below can be turned off per-deployment from Settings → Features, which hides it
 * from the sidebar and blocks its routes — without touching code. Anything not
 * listed here (Dashboard, Settings itself) is core and always on.
 */
class Features
{
    public const ALL = [
        'env' => 'Env (App, Maintenance & API Settings)',
        'blog' => 'Blog (Posts)',
        'taxonomy' => 'Categories & Tags (shared by Blog and Products)',
        'access-control' => 'Access Control (Roles, Permissions, Users)',
        'audit-log' => 'Audit Log',
        'products' => 'Products',
        'services' => 'Services',
        'orders' => 'Orders & Reports',
        'discounts' => 'Discounts (Products)',
        'pages' => 'Pages',
        'cms' => 'CMS',
        'media-library' => 'Media Library',
        'file-manager' => 'File Manager',
        'chat' => 'Chat',
        'contacts' => 'Contacts',
        'comments' => 'Comments (Posts, Products & Services)',
        'reviews' => 'Reviews (Posts, Products & Services)',
        'newsletter' => 'Newsletter (Subscribers)',
        'menu' => 'Menu Manager',
        'email-templates' => 'Email Templates',
        'localization' => 'Localization (Languages & Translations)',
        'location' => 'Location (Countries, Divisions, Districts, Upazilas)',
        'advance' => 'Advance (Sitemap & Robots.txt)',
    ];

    public static function enabled(string $key): bool
    {
        if (! array_key_exists($key, self::ALL)) {
            return true;
        }

        return (bool) Feature::enabledMapCached()->get($key, true);
    }

    public static function settingKey(string $key): string
    {
        return "feature_{$key}";
    }
}
