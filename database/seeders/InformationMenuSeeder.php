<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * The "Information" menu — the About Us / Contact Us / FAQ links rendered by
 * the footer's Information column in the frontend theme partials.
 * Mirrors a subset of the standalone pages PageSeeder creates.
 */
class InformationMenuSeeder extends Seeder
{
    private const ITEMS = [
        ['label' => 'About Us', 'url' => '/about'],
        ['label' => 'Contact Us', 'url' => '/contact'],
        ['label' => 'FAQ', 'url' => '/faq'],
    ];

    public function run(): void
    {
        Menu::firstOrCreate(['slug' => 'information'], ['name' => 'Information']);

        foreach (self::ITEMS as $index => $item) {
            MenuItem::updateOrCreate(
                ['group' => 'information', 'label' => $item['label']],
                ['url' => $item['url'], 'sort_order' => $index, 'is_group' => false, 'is_active' => true],
            );
        }
    }
}
