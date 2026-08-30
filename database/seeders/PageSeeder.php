<?php

namespace Database\Seeders;

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

        foreach (self::PAGES as ['file' => $file, 'sort_order' => $sortOrder]) {
            $filePath = "{$dataDir}/{$file}.json";

            if (! file_exists($filePath)) {
                $this->command->warn("PageSeeder: {$file}.json not found, skipping.");

                continue;
            }

            $json = json_decode(file_get_contents($filePath), true);

            Page::updateOrCreate(
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
                    'status' => 'active',
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }
}
