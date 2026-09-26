<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retires the portfolio's three content tables.
 *
 * Projects, Experience and Skills each had a table, a model, a factory and a
 * pair of admin CRUD screens, while the rest of the one-pager was settings. That
 * split meant the portfolio was edited from four different places, and the three
 * sections an owner is most often asked about were the three behind their own
 * sidebar entries. They are ordinary key/value lists in the settings table now,
 * edited next to the hero copy on Admin → Theme Settings.
 *
 * So this moves the data across and then drops the tables. The backfill is the
 * point: the owner may have replaced every seeded row with their own work, and
 * none of it can be recovered once the columns are gone.
 *
 * Guarded by hasTable() because the create migrations for these tables are
 * deleted along with them — on a fresh install the tables are never created, so
 * there is nothing to back up and nothing to drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_projects')) {
            $this->backfill('theme_portfolio_projects', 'portfolio_projects', fn (array $row) => [
                'title' => $this->localised($row['title'] ?? null),
                'description' => $this->localised($row['description'] ?? null),
                'icon' => (string) ($row['icon'] ?? ''),
                // A repeater row is flat — the admin screen hydrates every value
                // to a scalar — so the array column becomes the comma-separated
                // line PortfolioProfile::splitList() reads back.
                'tech' => implode(', ', array_filter(
                    (array) json_decode((string) ($row['tech'] ?? '[]'), true),
                    fn ($item) => is_string($item) && trim($item) !== ''
                )),
                'stats' => (string) ($row['stats'] ?? ''),
                'link' => (string) ($row['link'] ?? ''),
            ]);
        }

        if (Schema::hasTable('portfolio_experiences')) {
            $this->backfill('theme_portfolio_experiences', 'portfolio_experiences', fn (array $row) => [
                'role' => $this->localised($row['role'] ?? null),
                'company' => $this->retiredCompany($this->localised($row['company'] ?? null)),
                'period' => (string) ($row['period'] ?? ''),
                'description' => $this->localised($row['description'] ?? null),
            ]);
        }

        if (Schema::hasTable('portfolio_skills')) {
            $this->backfill('theme_portfolio_skills', 'portfolio_skills', fn (array $row) => [
                'name' => $this->localised($row['name'] ?? null),
                'group' => (string) ($row['group'] ?? ''),
                'icon' => (string) ($row['icon'] ?? ''),
                'description' => $this->localised($row['description'] ?? null),
            ]);
        }

        Schema::dropIfExists('portfolio_projects');
        Schema::dropIfExists('portfolio_experiences');
        Schema::dropIfExists('portfolio_skills');

        // The `portfolio` feature flag existed only to hide those three admin
        // routes behind a toggle. With the routes gone nothing reads it, so the
        // row would sit in the database claiming a feature that is permanently
        // off. Deleted through the query builder because Setting has no forget();
        // nothing caches it any more either, since the flag is out of Features::ALL
        // and therefore never read.
        DB::table('settings')->where('key', 'feature_portfolio')->delete();
    }

    public function down(): void
    {
        // The tables are not rebuilt. Their shape is gone along with the CRUD
        // screens that were the only thing that wrote them, and re-creating three
        // empty tables would be a worse answer than a migration that admits it
        // cannot be reversed. The data survives in the settings keys this
        // migration wrote, which is where the theme now reads it from.
    }

    /**
     * Write a table's rows into a repeater setting, then leave the setting alone
     * if the owner already filled it in on the theme settings screen.
     *
     * Not overwriting is the important half: this migration can run against an
     * install where the owner has already started using the new settings screen,
     * and their copy is newer than anything in the table.
     *
     * @param  callable(array<string, mixed>): array<string, string>  $toRow
     */
    private function backfill(string $key, string $table, callable $toRow): void
    {
        $existing = DB::table('settings')->where('key', $key)->value('value');

        if (is_string($existing) && trim($existing) !== '' && json_decode($existing, true) !== []) {
            return;
        }

        $rows = DB::table($table)
            // Only active, un-deleted rows: the storefront's read layer filtered
            // on exactly this, so carrying a draft across would put content on
            // the public page that the owner had deliberately unpublished.
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $rows = $rows
            ->map(fn (object $row) => array_map('trim', $toRow((array) $row)))
            // The same rule the read layer applies: a row with no identity field
            // is not a row, so it is not carried across.
            ->reject(fn (array $row) => collect($row)->filter()->isEmpty())
            ->values()
            ->all();

        if ($rows === []) {
            return;
        }

        // Setting::set(), not a query-builder write. Setting::get() reads
        // through Cache::rememberForever(), and only the model bumps the shared
        // settings cache version — writing the row behind its back fills the
        // database while the storefront keeps serving the pre-migration value,
        // which for these keys is "this setting does not exist". The result is a
        // portfolio whose projects and skills sections silently vanish until
        // something else happens to invalidate the cache.
        Setting::set($key, json_encode($rows));
    }

    /**
     * The primary-locale text out of a translatable JSON column.
     *
     * Falls back through the app's locale, then English, then any non-empty
     * value, so a row translated only into a language this install does not
     * default to still arrives with its content rather than as a blank card.
     */
    private function localised(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $translations = json_decode($value, true);

        if (! is_array($translations)) {
            return trim($value);
        }

        foreach ([app()->getLocale(), 'en', ...array_keys($translations)] as $locale) {
            $text = $translations[$locale] ?? null;

            if (is_string($text) && trim($text) !== '') {
                return trim($text);
            }
        }

        return '';
    }

    /**
     * Blank the placeholder employer names this app's seeder used to ship.
     *
     * "Your Company" reaching the migration means the row was never filled in by
     * a person, and a stranger reads it as one. An empty company renders as no
     * company line at all, which is an honest gap the owner is going to close.
     */
    private function retiredCompany(string $company): string
    {
        return in_array($company, ['Your Company', 'Previous Company'], true) ? '' : $company;
    }
};
