<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LanguageSeeder extends Seeder
{
    /**
     * Seed the language list, then import whatever already lives in lang/*.json so the
     * admin editor starts from the strings the app currently ships with.
     */
    public function run(): void
    {
        $languages = [
            [
                'code'        => 'en',
                'name'        => 'English',
                'native_name' => 'English',
                'direction'   => 'ltr',
                'flag'        => '🇬🇧',
                'is_active'   => true,
                'is_default'  => true,
                'sort_order'  => 1,
            ],
            [
                'code'        => 'bn',
                'name'        => 'Bengali',
                'native_name' => 'বাংলা',
                'direction'   => 'ltr',
                'flag'        => '🇧🇩',
                'is_active'   => true,
                'is_default'  => false,
                'sort_order'  => 2,
            ],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], $language);
        }

        $this->importJsonFiles();

        Translation::flushCache();
    }

    /**
     * Import lang/{locale}.json into the translations table for every seeded language.
     */
    protected function importJsonFiles(): void
    {
        $codes = Language::pluck('code');
        $keys  = [];

        foreach ($codes as $code) {
            $path = lang_path("{$code}.json");

            if (! File::exists($path)) {
                continue;
            }

            $lines = json_decode(File::get($path), true);

            if (! is_array($lines)) {
                $this->command?->warn("Skipped lang/{$code}.json — invalid JSON.");
                continue;
            }

            foreach ($lines as $key => $value) {
                if (! is_string($value)) {
                    continue;
                }

                $keys[(string) $key] = true;

                Translation::updateOrCreate(
                    ['group' => '*', 'key' => (string) $key, 'locale' => $code],
                    ['value' => $value],
                );
            }
        }

        // Every key needs a row per language so the editor can render a full grid,
        // including the blanks that still need translating.
        foreach (array_keys($keys) as $key) {
            foreach ($codes as $code) {
                Translation::firstOrCreate(
                    ['group' => '*', 'key' => $key, 'locale' => $code],
                    ['value' => null],
                );
            }
        }
    }
}
