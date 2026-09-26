<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * The "Portfolio" menu — a nav of its own for the portfolio theme
 * (resources/views/frontend/themes/portfolio/home.blade.php), kept separate
 * from the shared "Frontend" menu so switching themes doesn't reshuffle either.
 *
 * The portfolio theme is a single page, so every item is a section anchor
 * ("#projects", "#skills", ...) rather than a route. The theme's header
 * prefixes the site root to a bare fragment, so a link still lands on the
 * right section when visited from a secondary page like /about.
 *
 * The seeded ids match the section ids in home.blade.php; the admin can add
 * more at /admin/menu (CMS-authored sections included, whose ids are their
 * section name).
 */
class PortfolioMenuSeeder extends Seeder
{
    private const ITEMS = [
        ['label' => 'Home', 'url' => '#home'],
        ['label' => 'Projects', 'url' => '#projects'],
        ['label' => 'Experience', 'url' => '#experience'],
        ['label' => 'Technology', 'url' => '#technology'],
        ['label' => 'Contact', 'url' => '#contact'],
    ];

    public function run(): void
    {
        Menu::firstOrCreate(['slug' => 'portfolio'], ['name' => 'Portfolio']);

        foreach (self::ITEMS as $index => $item) {
            // Keyed on the anchor, not the label: the anchor is what the item
            // *is*, the label is admin-editable text. Keying on the label would
            // mean a re-seed after a rename adds the seeded label back, leaving
            // two items pointing at the same section and a visibly duplicated
            // nav link. Leaving an existing item alone also keeps admin changes
            // to its label, order and active flag across a re-seed.
            MenuItem::firstOrCreate(
                ['group' => 'portfolio', 'url' => $item['url']],
                ['label' => $item['label'], 'sort_order' => $index, 'is_group' => false, 'is_active' => true],
            );
        }
    }
}
