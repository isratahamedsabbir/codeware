<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * The "Quick Links" menu — the Home/Shop/shortcut links rendered by the
 * footer's Quick Links column in the frontend theme partials.
 */
class QuickLinksMenuSeeder extends Seeder
{
    private const ITEMS = [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'Shop', 'url' => '/shop'],
        ['label' => 'Blog', 'url' => '/blog'],
        ['label' => 'My Favorites', 'url' => '/favorites'],
    ];

    public function run(): void
    {
        Menu::firstOrCreate(['slug' => 'quick-links'], ['name' => 'Quick Links']);

        foreach (self::ITEMS as $index => $item) {
            MenuItem::updateOrCreate(
                ['group' => 'quick-links', 'label' => $item['label']],
                ['url' => $item['url'], 'sort_order' => $index, 'is_group' => false, 'is_active' => true],
            );
        }
    }
}
