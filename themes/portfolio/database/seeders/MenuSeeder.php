<?php

namespace Themes\Portfolio\Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * The "Portfolio" menu — a nav of its own for the portfolio theme
 * (themes/portfolio/home.blade.php), kept separate
 * from the shared "Frontend" menu so switching themes doesn't reshuffle either.
 *
 * The portfolio theme is a single page, so almost every item is a section anchor
 * ("#projects", "#skills", ...) rather than a route. The theme's header prefixes
 * the site root to a bare fragment, so a link still lands on the right section
 * when visited from a secondary page like /blog.
 *
 * The one exception is the blog, which is a real page of its own and therefore a
 * real path. It is stored as "/blog" rather than as a fragment so the header
 * treats it as an ordinary link: it does not get the site-root prefix, and
 * `is_active` on the current URL marks it while a visitor is reading a post.
 * The theme 404-guards /blog on itself (see themes/portfolio/routes.php), so the
 * nav item is dropped rather than left as a dead link on a site whose blog
 * feature is off.
 *
 * The seeded ids match the section ids in home.blade.php; the admin can add
 * more at /admin/menu (CMS-authored sections included, whose ids are their
 * section name).
 */
class MenuSeeder extends Seeder
{
    private const ITEMS = [
        ['label' => 'Home', 'url' => '#home'],
        ['label' => 'Services', 'url' => '#services'],
        ['label' => 'Projects', 'url' => '#projects'],
        ['label' => 'Experience', 'url' => '#experience'],
        ['label' => 'Technology', 'url' => '#technology'],
        ['label' => 'Testimonials', 'url' => '#testimonials'],
        ['label' => 'Contact', 'url' => '#contact'],
        ['label' => 'Blog', 'url' => '/blog'],
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
