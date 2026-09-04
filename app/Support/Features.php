<?php

namespace App\Support;

use App\Models\Feature;

/**
 * This admin panel is reused as a starting point across multiple projects, and not
 * every project needs every module (Chat, Blog, File Manager, ...). Each feature
 * below can be turned off per-deployment from Settings → Features, which hides it
 * from the sidebar and blocks its routes — without touching code. Anything not
 * listed here (Dashboard, Users, Roles, Settings itself, Activity History) is core
 * and always on.
 */
class Features
{
    public const ALL = [
        'blog' => 'Blog (Posts, Categories, Tags)',
        'products' => 'Products',
        'orders' => 'Orders & Reports',
        'pages' => 'Pages',
        'cms' => 'CMS',
        'media-library' => 'Media Library',
        'file-manager' => 'File Manager',
        'chat' => 'Chat',
        'contacts' => 'Contacts',
        'menu' => 'Menu Manager',
        'email-templates' => 'Email Templates',
        'localization' => 'Localization (Languages & Translations)',
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
