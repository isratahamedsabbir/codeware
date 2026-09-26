<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * The "Frontend" menu — the site nav rendered by the ecommerce theme's header
 * (resources/views/frontend/themes/ecommerce/partials/header.blade.php).
 * Mirrors the standalone pages PageSeeder creates.
 *
 * The portfolio theme has a nav of its own, see PortfolioMenuSeeder.
 */
class FrontendMenuSeeder extends Seeder
{
    private const ITEMS = [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'Shop', 'url' => '/shop'],
        ['label' => 'About Us', 'url' => '/about'],
        ['label' => 'Contact Us', 'url' => '/contact'],
        ['label' => 'FAQ', 'url' => '/faq'],
    ];

    public function run(): void
    {
        Menu::firstOrCreate(['slug' => 'frontend'], ['name' => 'Frontend']);

        foreach (self::ITEMS as $index => $item) {
            MenuItem::updateOrCreate(
                ['group' => 'frontend', 'label' => $item['label']],
                ['url' => $item['url'], 'sort_order' => $index, 'is_group' => false, 'is_active' => true],
            );
        }
    }
}
