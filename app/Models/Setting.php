<?php

namespace App\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'group', 'is_public'];

    /**
     * Settings that hold one value per language, stored as a JSON
     * `{code: value}` map in the same `value` column.
     *
     * A Setting row has no model instance to hang a translatable cast off — it
     * is read as a raw key=>value map out of the cache, once per request, for
     * every consumer — so the per-language values live inside the column
     * instead, and translations()/translated() decode them on the way out.
     *
     * The global SEO copy is per-language for the same reason it is on a Page:
     * a Bengali visitor is served a Bengali page, and an English meta
     * description on it is a wasted impression.
     */
    public const TRANSLATABLE = [
        'seo_meta_title',
        'seo_meta_description',
        'seo_og_title',
        'seo_og_description',
        'seo_twitter_title',
        'seo_twitter_description',
    ];

    public static function isTranslatable(string $key): bool
    {
        return in_array($key, self::TRANSLATABLE, true);
    }

    /**
     * The per-locale values of a translatable setting, given its stored value.
     *
     * Accepts a plain string as well as the JSON map, because the settings
     * seeded and saved before these became per-language hold a bare string:
     * that string belongs to the primary locale, so existing copy keeps
     * rendering after the change instead of silently going blank.
     *
     * @return array<string, string>
     */
    public static function translations(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_map(fn ($value) => (string) $value, $raw);
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [Locale::primary() => $raw];
        }

        return array_map(fn ($value) => (string) $value, $decoded);
    }

    /**
     * One locale's value out of a translatable setting's stored value, falling
     * back to the primary locale when the requested one was left empty — the
     * same fallback the translatable columns on the models use, so a page that
     * only translated its title still unfurls with a usable description.
     */
    public static function translate(mixed $raw, ?string $locale = null): ?string
    {
        $translations = self::translations($raw);

        if ($translations === []) {
            return null;
        }

        // An unfilled translation has to fall through to the primary one, so
        // this tests for content rather than for the key being there at all.
        foreach ([$locale ?? Locale::current(), Locale::primary()] as $candidate) {
            if (filled($translations[$candidate] ?? null)) {
                return (string) $translations[$candidate];
            }
        }

        return null;
    }

    /**
     * translate() for a stored setting rather than a raw column value.
     */
    public static function translated(string $key, ?string $locale = null): ?string
    {
        return self::translate(self::get($key), $locale);
    }

    /**
     * Request-scoped memos so hot paths ($currencySymbol, $siteName, perPage(),
     * vatRate(), ...) don't re-read the cache store on every call — with a
     * database-backed cache store, each Cache::rememberForever() is a SELECT.
     *
     * Keyed by the cache repository instance (spl_object_id) so the memo dies
     * with the bootstrap that owns the cache: per PHP-FPM request in
     * production, per app instance in the test suite.
     */
    private static array $version = [];

    private static array $allCache = [];

    /**
     * The cache repository this bootstrap owns, as a memo key — unique per
     * PHP-FPM request and per app instance in the test suite.
     */
    private static function memoKey(): int
    {
        return spl_object_id(Cache::getFacadeRoot());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::allCached();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        self::$allCache = [];
        self::$version = [];

        // Bumping the version orphans the map key built from the old version —
        // including derived caches like the public settings list — without
        // needing cache tagging, which only redis/memcached/array support.
        // Which store is actually used is driven entirely by CACHE_STORE in .env.
        // Store the new version in the memo as well — cacheVersion() re-reads
        // the *old* value into the (just-cleared) memo before this write, so
        // leave that memo pointing at the value we just persisted.
        $next = self::cacheVersion() + 1;
        Cache::forever('settings:cache-version', $next);
        self::$version[self::memoKey()] = $next;
    }

    /**
     * The whole settings table as a single version-keyed cached map, instead of
     * one cache entry per key (a database cache store turns each of those into
     * its own SELECT — the storefront read scads of them, currency_symbol and
     * friends getting re-read a dozen times a page).
     *
     * @return array<string, mixed>
     */
    public static function allCached(): array
    {
        $key = self::memoKey();

        if (! array_key_exists($key, self::$allCache)) {
            self::$allCache[$key] = Cache::rememberForever('settings:all:v'.self::cacheVersion(), function () {
                return static::query()->pluck('value', 'key')->all();
            });
        }

        return self::$allCache[$key];
    }

    public static function cacheVersion(): int
    {
        $key = self::memoKey();

        return self::$version[$key] ??= (int) Cache::rememberForever('settings:cache-version', fn () => 1);
    }

    /**
     * Default row count for paginated admin list tables and the public API —
     * the single place every ->paginate() call reads from, driven by
     * Settings → General → Pagination.
     */
    public static function perPage(): int
    {
        return (int) static::get('pagination_per_page', 10);
    }

    /**
     * How long a minted Puck editor token (Sanctum PAT) stays valid — the
     * single place every openPuckEditor()/saveAndOpenPageBuilder() call
     * reads from, driven by the Settings button on the admin Pages screen.
     */
    public static function puckSessionMinutes(): int
    {
        return (int) static::get('puck_session_minutes', 30);
    }

    /**
     * The fulfillment status past which an order can no longer be cancelled —
     * see Order::canBeCancelled(). Driven by the "Cancellation Rule" settings
     * modal on the admin Orders screen.
     */
    public static function orderCancellationCutoffStatus(): string
    {
        return static::get('order_cancellation_cutoff_status', 'shipped');
    }

    /**
     * Stock level at or below which a product counts as "low stock" and gets
     * its warning row on the admin Products list — driven by the Stock
     * Settings modal on that screen.
     */
    public static function productMinStockQuantity(): int
    {
        return (int) static::get('product_min_stock_quantity', 10);
    }

    /**
     * Whether VAT is applied to orders — driven by the VAT toggle in
     * Settings → Currency. Off by default.
     */
    public static function vatEnabled(): bool
    {
        return (bool) static::get('vat_enabled', false);
    }

    /**
     * Whether the "Additional Data" card shows on the admin product form —
     * toggled from Settings → Widgets → Product Additional Data.
     */
    public static function productAdditionalDataEnabled(): bool
    {
        return self::truthy(static::get('additional_data_products_enabled', '1'));
    }

    /**
     * Whether the "Additional Data" card shows on the admin blog post form —
     * toggled from Settings → Widgets → Blog Post Additional Data.
     */
    public static function postAdditionalDataEnabled(): bool
    {
        return self::truthy(static::get('additional_data_posts_enabled', '1'));
    }

    /**
     * Boolean settings live in a plain string column and toggle rows are read
     * back as the raw strings "1"/"0" (not PHP booleans), so a naive (bool)
     * cast would treat the string "0" as enabled. Interpret the stored value
     * instead.
     */
    private static function truthy(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || strtolower((string) $value) === 'true';
    }

    /**
     * VAT percentage applied to orders when vatEnabled() is true — the
     * "VAT Rate" field in Settings → Currency.
     */
    public static function vatRate(): float
    {
        return (float) static::get('vat_rate', 0);
    }

    /**
     * The tax label shown next to the VAT line on order totals (e.g. "VAT"
     * or "Sales Tax") — Settings → Currency → "VAT Label".
     */
    public static function vatLabel(): string
    {
        return (string) static::get('vat_label', 'VAT');
    }

    /**
     * The VAT amount owed on a given taxable total (the order's discounted
     * subtotal) at the configured rate — zero when VAT is disabled. This is
     * the single place every order pipeline reads from, so the storefront
     * checkout, the order API and any future totals all agree.
     */
    public static function vatFor(float $taxable): float
    {
        if (! self::vatEnabled()) {
            return 0.0;
        }

        return round($taxable * (self::vatRate() / 100), 2);
    }
}
