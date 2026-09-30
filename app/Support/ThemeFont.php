<?php

namespace App\Support;

/**
 * The typeface a storefront theme renders in, chosen per theme from
 * Admin → Theme Settings.
 *
 * This is the storefront's counterpart to App\Support\AdminFont, and the two are
 * deliberately the same shape: a small table of options, each with a stack, the
 * files it needs, and a rule for preloading. They are separate classes because
 * they answer to separate screens and share nothing — picking a face for the
 * panel must not be able to restyle the public site, or the reverse.
 *
 * The one option that is not a face is THEME_DEFAULT, an empty value meaning
 * "whatever this theme already looks like". That is the important one: it is what
 * a theme has before anyone touches the setting, and it is why adding this
 * feature changed no theme's appearance. Every theme here ships a font chosen to
 * go with its design — Instrument Sans for portfolio, Trebuchet MS for
 * ecommerce, Plus Jakarta Sans for default — and a font picker that overwrote
 * those on install would be a bug, not a feature.
 *
 * A theme's own font is declared in its own CSS, and those declarations are not
 * uniform: the default theme reads var(--font-sans), ecommerce has a hard-coded
 *
 * @utility on the body, and portfolio sets a class on the body. So the choice is
 * applied by emitting a body font-family after the stylesheet (see
 * resources/views/partials/head.blade.php) rather than by writing a token the
 * themes already read — they do not read one, and making them would mean editing
 * three themes' CSS to add a feature that has to work for a theme installed
 * later too.
 */
class ThemeFont
{
    /**
     * The null option: leave the theme's own typography alone.
     *
     * Stored as the empty string rather than a sentinel slug, so an untouched
     * field round-trips as blank and "unset" looks unset on the screen.
     */
    public const THEME_DEFAULT = '';

    /**
     * The OS's own UI font, whatever that is.
     */
    public const SYSTEM = 'system';

    /**
     * A bundled Windows-and-Mac system face, and ecommerce's current storefront
     * font. Listed because a theme that is already using it can keep it while
     * everything else moves to a webfont.
     */
    public const TREBUCHET = 'trebuchet';

    /**
     * Self-hosted, latin + latin-ext. See resources/css/fonts.css.
     */
    public const PLUS_JAKARTA = 'plus-jakarta';

    /**
     * Self-hosted, latin. Portfolio's own face, and the one it looks designed
     * around.
     */
    public const INSTRUMENT_SANS = 'instrument-sans';

    /**
     * Self-hosted, latin + latin-ext. The same files the admin panel offers.
     */
    public const ROBOTO = 'roboto';

    /**
     * The label each stored value is offered under, in the order they are offered.
     *
     * THEME_DEFAULT is first and labelled as the theme's own rather than as a
     * font, because that is what it is: the safe choice, and the one that puts
     * the theme back the way its designer left it.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::THEME_DEFAULT => 'Theme default',
            self::SYSTEM => 'System font',
            self::TREBUCHET => 'Trebuchet MS',
            self::PLUS_JAKARTA => 'Plus Jakarta Sans',
            self::INSTRUMENT_SANS => 'Instrument Sans',
            self::ROBOTO => 'Roboto',
        ];
    }

    /**
     * The stack for each option, keyed by stored value.
     *
     * Every one ends in a generic tail, so text still renders if none of the
     * named faces are present — which is the normal case for a webfont whose file
     * has not arrived yet, and the case a stack of only 'Roboto' would turn into
     * a page in the browser's default.
     *
     * The webfont stacks lead with their own family rather than naming it later,
     * because choosing one over the theme default is a decision to see that face
     * even where a system font of a similar name exists.
     *
     * @return array<string, string>
     */
    public static function stacks(): array
    {
        return [
            self::SYSTEM => "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            self::TREBUCHET => "'Trebuchet MS', 'Segoe UI', ui-sans-serif, system-ui, sans-serif",
            self::PLUS_JAKARTA => "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif",
            self::INSTRUMENT_SANS => "'Instrument Sans', system-ui, -apple-system, sans-serif",
            self::ROBOTO => "'Roboto', 'Helvetica Neue', Arial, sans-serif",
        ];
    }

    /**
     * The @font-face sources for an option, keyed by stored value.
     *
     * Only webfonts appear; a system face has no file, so declaring one would be
     * inventing a download. THEME_DEFAULT appears as an empty list — there is
     * nothing to add, because the theme's own stylesheet already declares
     * whatever it uses.
     *
     * Every webfont is declared here rather than relied on from an existing
     * stylesheet, and that is not redundancy. Which stylesheet a page loads
     * depends on the theme: Themes::storefrontEntry() gives a theme with its own
     * stylesheet that file, and the storefront bundle — the only thing that pulls
     * in resources/css/fonts.css and its Plus Jakarta faces — goes to the themes
     * that have none. Portfolio, which ships its own, never sees fonts.css at
     * all. So a face declared only in fonts.css simply does not exist on the
     * portfolio pages, and picking Plus Jakarta there would silently render
     * nothing. Emitting the faces for whichever option is selected is what makes
     * the picker mean the same thing on every theme, including one installed
     * later as a zip.
     *
     * Only the upright face is declared. These are body faces, and no theme sets
     * italic body copy; a browser synthesises an oblique from the upright file
     * rather than paying for a second download. (portfolio/fonts.css does declare
     * an italic Instrument Sans for its own headings, and that still applies.)
     *
     * The ranges are Google's, copied from resources/css/fonts.css rather than
     * retyped, and they are the mechanism that keeps a file out of the critical
     * path: a page with no character outside a range never downloads the file for
     * that range, so the latin-ext file is spent only by pages that have a
     * character in it. Bengali is deliberately absent from every option — it
     * falls through to a system Bengali face rather than making the common case
     * carry a large third file.
     *
     * @return array<string, array<int, array{url: string, unicodeRange: string}>>
     */
    public static function facesFor(mixed $value): array
    {
        $value = self::normalize($value);

        $latin = 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD';
        $latinExt = 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF';

        $faces = [
            self::PLUS_JAKARTA => [
                ['url' => '/fonts/plus-jakarta-sans-latin-ext.woff2', 'unicodeRange' => $latinExt],
                ['url' => '/fonts/plus-jakarta-sans-latin.woff2', 'unicodeRange' => $latin],
            ],
            self::INSTRUMENT_SANS => [
                ['url' => '/fonts/instrument-sans-latin.woff2', 'unicodeRange' => $latin],
            ],
            self::ROBOTO => [
                ['url' => '/fonts/roboto-latin-ext.woff2', 'unicodeRange' => $latinExt],
                ['url' => '/fonts/roboto-latin.woff2', 'unicodeRange' => $latin],
            ],
        ];

        return isset($faces[$value]) ? [$value => $faces[$value]] : [];
    }

    /**
     * The font-family each @font-face declares, per option.
     *
     * Kept beside the files rather than in the view because the family name and
     * the file that serves it have to agree, and nothing forces them to: a typo in
     * a view would produce a face that downloads and is never used.
     *
     * @return array<string, string>
     */
    public static function familiesFor(mixed $value): array
    {
        $families = [
            self::PLUS_JAKARTA => "'Plus Jakarta Sans'",
            self::INSTRUMENT_SANS => "'Instrument Sans'",
            self::ROBOTO => "'Roboto'",
        ];

        $value = self::normalize($value);

        return isset($families[$value]) ? [$value => $families[$value]] : [];
    }

    /**
     * The file to preload for an option, or null when it has none.
     *
     * Only ever the *latin* subset. Preloading a subset the page may not use is
     * how a preload becomes a speculative download: the browser has to be told to
     * skip the fetch when unicode-range would not have matched, and until it
     * does, the file is a request the preloader cannot cancel. Latin covers
     * ordinary body text, so preloading it is a safe bet; latin-ext is left to
     * the range check that happens anyway.
     *
     * @return non-empty-string|null
     */
    public static function preloadFor(mixed $value): ?string
    {
        $value = self::normalize($value);

        $preloads = [
            self::PLUS_JAKARTA => '/fonts/plus-jakarta-sans-latin.woff2',
            self::INSTRUMENT_SANS => '/fonts/instrument-sans-latin.woff2',
            self::ROBOTO => '/fonts/roboto-latin.woff2',
        ];

        return $preloads[$value] ?? null;
    }

    /**
     * The weight axis each webfont option's file covers, per option.
     *
     * Declared so one file serves every weight the storefront uses. A per-weight
     * list here would be four downloads instead of one, and a wrong range is
     * worse than a missing one: a weight outside the axis is rendered with the
     * nearest declared weight rather than the one asked for, so 700 in a face
     * that claims 400-500 comes out looking like 600.
     *
     * @return array<string, string>
     */
    public static function weightsFor(mixed $value): array
    {
        $value = self::normalize($value);

        $weights = [
            self::PLUS_JAKARTA => '200 800',
            self::INSTRUMENT_SANS => '400 700',
            self::ROBOTO => '100 900',
        ];

        return isset($weights[$value]) ? [$value => $weights[$value]] : [];
    }

    /**
     * The stack to apply for a stored setting value, or null to leave the theme
     * alone.
     *
     * Null for THEME_DEFAULT and for anything unrecognised. A settings row can be
     * hand-edited, or left over from a version that offered different choices,
     * and an unknown value has to mean "no opinion" — resolving it to a font
     * would silently restyle a theme nobody asked to change.
     *
     * @return non-empty-string|null
     */
    public static function stackFor(mixed $value): ?string
    {
        $value = self::normalize($value);

        return $value === self::THEME_DEFAULT ? null : (self::stacks()[$value] ?? null);
    }

    /**
     * The value to store, normalised against the options that exist.
     *
     * THEME_DEFAULT is preserved rather than replaced, which is the opposite of
     * how App\Support\AdminFont treats an unknown value and is deliberate: here
     * blank is a real choice, and there blank is a bug.
     */
    public static function normalize(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return array_key_exists($value, self::options()) ? $value : self::THEME_DEFAULT;
    }
}
