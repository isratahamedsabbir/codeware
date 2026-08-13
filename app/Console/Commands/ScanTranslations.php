<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class ScanTranslations extends Command
{
    protected $signature = 'translations:scan
                            {--prune : Delete keys that no longer appear in the source}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Scan the codebase for __() strings and add any missing keys to the translations table';

    /**
     * Matches __('key'), __("key"), @lang('key') and trans('key') — first argument only.
     * Keys containing an unescaped matching quote are not supported (and never occur in
     * practice for UI strings).
     */
    protected const PATTERN = '/(?:__|trans|@lang)\(\s*([\'"])((?:\\\\.|(?!\1).)+)\1/s';

    public function handle(): int
    {
        $locales = Language::pluck('code')->all();

        if ($locales === []) {
            $this->components->error('No languages found. Run `php artisan db:seed --class=LanguageSeeder` first.');

            return self::FAILURE;
        }

        $default = Language::where('is_default', true)->value('code') ?? $locales[0];
        $dryRun  = (bool) $this->option('dry-run');

        $found = $this->scan();

        if ($found === []) {
            $this->components->warn('No translatable strings found.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            'Found %d unique key(s) across %d locale(s).',
            count($found),
            count($locales),
        ));

        $created = 0;
        $skipped = 0;

        foreach ($found as $key) {
            // The unique index is on a 191-char column; anything longer cannot be stored.
            if (mb_strlen($key) > 191) {
                $skipped++;
                $this->line("  <fg=yellow>too long, skipped:</> {$this->preview($key)}");
                continue;
            }

            foreach ($locales as $locale) {
                $exists = Translation::where('group', '*')
                    ->where('key', $key)
                    ->where('locale', $locale)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $created++;

                if (! $dryRun) {
                    Translation::create([
                        'group'  => '*',
                        'key'    => $key,
                        'locale' => $locale,
                        // The default locale reads naturally from the key itself; other
                        // locales start blank so they show up as untranslated.
                        'value'  => $locale === $default ? $key : null,
                    ]);
                }
            }
        }

        $pruned = $this->option('prune') ? $this->prune($found, $dryRun) : 0;

        if (! $dryRun) {
            Translation::flushCache();
        }

        $this->newLine();
        $this->components->twoColumnDetail('Rows added', (string) $created);

        if ($this->option('prune')) {
            $this->components->twoColumnDetail('Rows pruned', (string) $pruned);
        }

        if ($skipped > 0) {
            $this->components->twoColumnDetail('Keys skipped (over 191 chars)', (string) $skipped);
        }

        if ($dryRun) {
            $this->components->warn('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }

    /**
     * All distinct translation keys referenced in views, Livewire components and app code.
     *
     * @return array<int, string>
     */
    protected function scan(): array
    {
        $paths = array_filter([
            resource_path('views'),
            app_path(),
            base_path('routes'),
        ], fn (string $path) => File::isDirectory($path));

        $finder = Finder::create()->files()->in($paths)->name('*.php');

        $keys = [];

        foreach ($finder as $file) {
            if (preg_match_all(self::PATTERN, $file->getContents(), $matches)) {
                foreach ($matches[2] as $index => $raw) {
                    $key = $this->unescape($raw, $matches[1][$index]);

                    // Skip config-style / dotted group keys and interpolated keys — those
                    // are file-based or dynamic, not admin-editable UI strings.
                    if ($key === '' || str_contains($key, '$') || preg_match('/^[a-z0-9_-]+(\.[a-z0-9_.-]+)+$/i', $key)) {
                        continue;
                    }

                    $keys[$key] = true;
                }
            }
        }

        return array_keys($keys);
    }

    /**
     * Remove rows whose key is no longer referenced anywhere in the source.
     *
     * @param  array<int, string>  $found
     */
    protected function prune(array $found, bool $dryRun): int
    {
        $stale = Translation::where('group', '*')
            ->whereNotIn('key', $found)
            ->pluck('key')
            ->unique();

        if ($stale->isEmpty()) {
            return 0;
        }

        foreach ($stale as $key) {
            $this->line("  <fg=red>prune:</> {$this->preview($key)}");
        }

        $count = Translation::where('group', '*')->whereIn('key', $stale)->count();

        if (! $dryRun) {
            Translation::where('group', '*')->whereIn('key', $stale)->delete();
        }

        return $count;
    }

    protected function unescape(string $raw, string $quote): string
    {
        return str_replace(['\\'.$quote, '\\\\'], [$quote, '\\'], $raw);
    }

    protected function preview(string $key): string
    {
        return mb_strlen($key) > 60 ? mb_substr($key, 0, 60).'…' : $key;
    }
}
