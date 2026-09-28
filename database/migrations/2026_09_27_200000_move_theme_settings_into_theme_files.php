<?php

use App\Support\ThemeSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Moves every theme's settings out of the `settings` table and into the
 * theme.json the theme folder already has.
 *
 * Theme settings used to be rows in the shared settings table under a
 * `theme_{slug}_*` prefix. Three themes with a couple of dozen fields each buried
 * the site's own settings hundreds of rows deep, and no theme could be handed to
 * anyone — downloaded, re-uploaded, copied to another site — with its content
 * still in it, because the content was in the database and the theme was a folder
 * of blade files. It lives with the theme now (see App\Support\ThemeSettings).
 *
 * Written into the existing theme.json rather than a new file beside it, so a
 * theme keeps one JSON file and the manifest it already had stays intact:
 * ThemeSettings::merge() writes the settings in without touching the name,
 * description, version, author or tags around them.
 *
 * Rows are matched by the theme folder they belong to, not by parsing the prefix
 * out of the key: slugs may contain underscores, so `theme_a_b_c` is ambiguous,
 * and the folder is the authority anyway.
 *
 * Two kinds of value are recognised by what they are rather than by a list of
 * known repeater keys: anything whose stored text decodes to a JSON list is
 * written as a real array, everything else as a string. That way a repeater
 * added to a theme after this migration ran still moves correctly, and a scalar
 * that happens to be valid JSON is not silently turned into a list.
 *
 * A `theme_*` row whose theme folder is not installed is deliberately left
 * where it is. It is unreachable from the admin screen and from the storefront
 * either way, and deleting it would throw away content that comes back intact if
 * that theme is ever re-uploaded.
 */
return new class extends Migration
{
    public function up(): void
    {
        $moved = 0;

        foreach ($this->themeSlugs() as $slug) {
            $rows = DB::table('settings')->where('key', 'like', 'theme\_'.$slug.'\_%')->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $values = [];

            foreach ($rows as $row) {
                $values[$row->key] = $this->decoded($row->value);
            }

            ThemeSettings::merge($slug, $values);

            DB::table('settings')->whereIn('key', array_keys($values))->delete();

            $moved += $rows->count();
        }

        // The whole settings map is cached under a version key that only
        // Setting::set() bumps, and this migration deletes rows behind its back.
        // Without this the storefront would keep rendering a theme's colours out
        // of a map that still lists them until the cache happened to expire.
        if ($moved > 0) {
            Cache::forever('settings:cache-version', (int) Cache::get('settings:cache-version') + 1);
        }
    }

    public function down(): void
    {
        foreach ($this->themeSlugs() as $slug) {
            $values = ThemeSettings::all($slug);

            $rows = [];

            foreach ($values as $key => $value) {
                if (! str_starts_with($key, 'theme_'.$slug.'_')) {
                    continue;
                }

                $rows[] = [
                    'key' => $key,
                    // A list goes back as the JSON text the column held, so
                    // down() restores the table exactly as up() found it.
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type' => is_array($value) ? 'json' : 'string',
                    'group' => 'frontend',
                    'is_public' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($rows !== []) {
                DB::table('settings')->upsert($rows, ['key'], ['value', 'updated_at']);
            }
        }

        Cache::forever('settings:cache-version', (int) Cache::get('settings:cache-version') + 1);
    }

    /**
     * The theme folders on disk, read directly rather than through
     * Themes::all() — that caches its scan for a day, and a migration should see
     * the filesystem as it is right now, not as some earlier request left it.
     *
     * @return array<int, string>
     */
    private function themeSlugs(): array
    {
        $path = resource_path('views/frontend/themes');

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::directories($path))
            ->map(fn (string $directory) => basename($directory))
            ->filter(fn (string $slug) => ThemeSettings::isValidSlug($slug))
            ->values()
            ->all();
    }

    /**
     * A stored value as it should be written into the theme's file: a list stays
     * a list, everything else stays the text it was.
     */
    private function decoded(mixed $value): mixed
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) && array_is_list($decoded) ? $decoded : $value;
    }
};
