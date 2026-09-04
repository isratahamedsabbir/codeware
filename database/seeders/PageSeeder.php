<?php

namespace Database\Seeders;

use App\Models\CmsSection;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    private const PAGES = [
        ['file' => 'home',    'sort_order' => 0],
        ['file' => 'about',   'sort_order' => 1],
        ['file' => 'contact', 'sort_order' => 2],
        ['file' => 'faq',     'sort_order' => 3],
        // Inactive — Fortify serves /login directly (see FortifyServiceProvider),
        // never through FrontendController::page(). This row only exists so its
        // SEO title/description are editable from /admin/pages; kept inactive so
        // it never shows up in navPages() as a site-nav link.
        ['file' => 'login',   'sort_order' => 4, 'status' => 'inactive'],
    ];

    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();
        $dataDir = base_path('data/pages');

        // Remove standalone pages left over from a previous version of this list
        // (e.g. an old demo's "products"/"media" pages) so re-seeding actually
        // converges on the current PAGES set instead of just leaving them behind.
        Page::where('type', 'page')
            ->whereNotIn('slug', collect(self::PAGES)->pluck('file'))
            ->delete();

        foreach (self::PAGES as $entry) {
            $file = $entry['file'];
            $sortOrder = $entry['sort_order'];
            $filePath = "{$dataDir}/{$file}.json";

            if (! file_exists($filePath)) {
                $this->command->warn("PageSeeder: {$file}.json not found, skipping.");

                continue;
            }

            $json = json_decode(file_get_contents($filePath), true);

            $page = Page::updateOrCreate(
                ['slug' => $file],
                [
                    'user_id' => $admin?->id,
                    'title' => ['en' => $json['title'] ?? Str::title($file), 'bn' => ''],
                    'seo_description' => $json['description'] ?? '',
                    'puck_data' => [
                        'root' => $json['root'] ?? ['props' => []],
                        'content' => $json['content'] ?? [],
                    ],
                    'template' => 'puck',
                    'status' => $entry['status'] ?? 'active',
                    'sort_order' => $sortOrder,
                ]
            );

            foreach ($json['cms'] ?? [] as $cmsSortOrder => $section) {
                CmsSection::updateOrCreate(
                    ['page_id' => $page->id, 'name' => $section['name']],
                    [
                        'cards' => $section['cards'] ?? [],
                        'constant' => $section['constant'] ?? [],
                        'status' => $section['status'] ?? 'active',
                        'sort_order' => $cmsSortOrder,
                    ]
                );
            }
        }
    }
}
