<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retires the theme_portfolio_services repeater in favour of Service rows.
 *
 * A bookable service has to be a row, not a settings value: the storefront form
 * posts a service id and the booking table holds a foreign key to it. So the
 * "Services" block on the portfolio one-pager is no longer a repeater edited on
 * Theme Settings, it is the existing Services CRUD screen, and the repeater is
 * removed.
 *
 * The rows are carried across rather than dropped, for the same reason
 * 2026_09_26_000003 backfilled the three retired tables instead of discarding
 * them: an owner who replaced the seeded rows with their own copy has real
 * content here, and it cannot be recovered once the key is gone. The old `icon`
 * field is the one thing not carried over - services has no icon column, and
 * inventing one for a field nothing else reads would be scope for its own sake.
 *
 * Guarded by exists() because this can run against an install where the owner has
 * already built out the Services screen. Their rows are the newer copy of the
 * same idea, so the repeater is dropped without seeding duplicates on top.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $rows = json_decode((string) DB::table('settings')->where('key', 'theme_portfolio_services')->value('value'), true);

        if (! Service::withTrashed()->exists()) {
            $this->carry($rows);
        }

        // Query builder rather than Setting: nothing reads this key any more, so
        // there is no cache entry to bust. The sibling retirement migration
        // deletes its dead feature flag the same way.
        DB::table('settings')->where('key', 'theme_portfolio_services')->delete();
    }

    public function down(): void
    {
        // Not restored. The rows now live in `services`, where the storefront
        // and the booking form read them from, and rebuilding the repeater would
        // put a second, unedited copy of the same content back in the settings
        // table - which is the split this migration exists to close.
    }

    /**
     * Write the repeater's rows into `services`, in their existing order.
     *
     * Both locales, the way the translatable factories and the seeder do it: the
     * en value is the real one and bn is deliberately empty, so the storefront
     * falls back to en rather than showing copy nobody wrote. Service's `saving`
     * hook fills the unique slug from the en name.
     */
    private function carry(mixed $rows): void
    {
        if (! is_array($rows)) {
            return;
        }

        $rows = collect($rows)
            // A repeater row is flat and may have been hand-edited in the
            // settings table, so every value is read as a string defensively.
            ->map(fn ($row) => is_array($row) ? array_map(
                fn ($value) => is_string($value) ? trim($value) : '',
                $row
            ) : null)
            // Same rule the repeater read layer applies: a row with no title is
            // not a row, so it is not carried across.
            ->filter(fn (?array $row) => $row !== null && ($row['title'] ?? '') !== '')
            ->values();

        foreach ($rows as $index => $row) {
            Service::create([
                'name' => ['en' => $row['title'], 'bn' => ''],
                'description' => ['en' => $row['description'] ?? '', 'bn' => ''],
                // Active, because the storefront only ever showed the repeater's
                // rows - the repeater had no draft state to have hidden one.
                'status' => 'active',
                'sort_order' => $index,
            ]);
        }
    }
};
