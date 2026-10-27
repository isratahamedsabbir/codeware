<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * When a product link opens the campaign landing page instead of the regular
 * product page. The admin sets it on Products → Landing Page: one rule per
 * line, matched against the visit's query string.
 *
 *   fbclid               the parameter is present and not empty
 *   utm_source=facebook  the parameter equals the value (case-insensitive)
 *
 * A visit that matches any rule gets the landing page; no rules means the
 * landing pages are never shown. Out of the box it is `fbclid`, so a link from
 * a Facebook ad behaves as it always has.
 */
class LandingTriggers
{
    public const SETTING = 'landing_page_triggers';

    public const DEFAULT = 'fbclid';

    /** A rule is a parameter name, optionally followed by =value. */
    public const RULE_PATTERN = '/^[A-Za-z0-9_-]+(=[^\s=]+)?$/';

    public const MAX_RULES = 20;

    /**
     * The rules as the admin typed them, one per line.
     */
    public static function raw(): string
    {
        return (string) Setting::get(self::SETTING, self::DEFAULT);
    }

    /**
     * @return list<string>
     */
    public static function rules(?string $raw = null): array
    {
        $lines = preg_split('/\R/', $raw ?? self::raw()) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));
    }

    public static function matches(Request $request, ?string $raw = null): bool
    {
        // Raw query bag, not $request->query(): that one reads "a.b" as a nested
        // key, and a rule is always one flat parameter name.
        $query = $request->query->all();

        foreach (self::rules($raw) as $rule) {
            [$name, $value] = array_pad(explode('=', $rule, 2), 2, null);

            $given = $query[$name] ?? null;

            if (! is_string($given) || $given === '') {
                continue;
            }

            if ($value === null || strcasecmp($given, $value) === 0) {
                return true;
            }
        }

        return false;
    }
}
