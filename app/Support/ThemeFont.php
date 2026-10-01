<?php

namespace App\Support;

/**
 * The typeface a storefront theme renders in, chosen per theme from
 * Admin → Theme Settings → Typography.
 *
 * Fonts are discovered from the filesystem, one folder per family, in the same
 * public/fonts/{font-name}/ folders the admin panel uses (see AdminFont, whose
 * file-name conventions for weight, italic and latin / latin-ext apply). A font
 * is stored once and offered everywhere; which font each theme (and the panel)
 * actually uses is a separate setting for each.
 *
 * Two options are always present:
 *   - THEME_DEFAULT (stored as ''): leave the theme's own typography alone. The
 *     default, and what every theme has until someone touches the setting.
 *   - SYSTEM: the OS's own UI font.
 *
 * A stored value whose folder has been deleted (or that never existed) resolves
 * to THEME_DEFAULT: the theme simply renders as it ships, with no error and no
 * declaration pointing at a file that is gone.
 *
 * The choice is applied by emitting a body font-family after the stylesheet (see
 * resources/views/partials/head.blade.php), since the three themes declare their
 * font three different ways and none read a shared token.
 */
class ThemeFont
{
    /**
     * Leave the theme's own typography alone. Stored as the empty string, so an
     * untouched field round-trips as blank.
     */
    public const THEME_DEFAULT = '';

    /**
     * The OS's own UI font, whatever that is.
     */
    public const SYSTEM = 'system';

    private const SYSTEM_STACK = "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";

    /**
     * Stacks of the faces the themes ship with, used only to preview "Theme
     * default" in the picker (see its own-face prop). They are not options.
     */
    private const OWN_STACKS = [
        'trebuchet' => "'Trebuchet MS', 'Segoe UI', ui-sans-serif, system-ui, sans-serif",
        'plus-jakarta' => "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif",
        'instrument-sans' => "'Instrument Sans', system-ui, -apple-system, sans-serif",
    ];

    /**
     * The label each stored value is offered under for a theme: theme default,
     * system font, then the theme's font folders.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::THEME_DEFAULT => 'Theme default',
            self::SYSTEM => 'System font',
        ] + AdminFont::folders();
    }

    /**
     * The stack for each option of a theme, keyed by stored value, plus the
     * built-in "own" stacks so the picker can preview Theme default.
     *
     * Every stack ends in a generic tail, so text still renders if a file never
     * arrives, and a webfont leads with its own family so it wins over a
     * similarly-named system face.
     *
     * @return array<string, string>
     */
    public static function stacks(): array
    {
        $stacks = self::OWN_STACKS + [self::SYSTEM => self::SYSTEM_STACK];

        foreach (array_keys(AdminFont::folders()) as $name) {
            $stacks[$name] = "'{$name}', ".self::SYSTEM_STACK;
        }

        return $stacks;
    }

    /**
     * The @font-face descriptors for an option of a theme; empty for the theme
     * default and the system font, which have no file.
     *
     * Declared for whichever option is selected rather than relied on from a
     * stylesheet, because which stylesheet a page loads depends on the theme.
     *
     * @return list<array{family: string, url: string, format: string, weight: string, style: string, unicodeRange: ?string}>
     */
    public static function facesFor(mixed $value): array
    {
        $value = self::normalize($value);

        if (! isset(AdminFont::folders()[$value])) {
            return [];
        }

        return AdminFont::facesIn(AdminFont::SCOPE, $value);
    }

    /**
     * The file to preload for an option of a theme, or null: only ever a latin
     * subset, since a preload the page does not use cannot be cancelled.
     *
     * @return non-empty-string|null
     */
    public static function preloadFor(mixed $value): ?string
    {
        return AdminFont::preloadOf(self::facesFor($value));
    }

    /**
     * The stack to apply for a stored value, or null to leave the theme alone.
     *
     * Null for THEME_DEFAULT and for anything unrecognised — including a font
     * whose folder was deleted. An unknown value has to mean "no opinion":
     * resolving it to a font would silently restyle a theme nobody asked to
     * change.
     *
     * @return non-empty-string|null
     */
    public static function stackFor(mixed $value): ?string
    {
        $value = self::normalize($value);

        return $value === self::THEME_DEFAULT ? null : (self::stacks()[$value] ?? null);
    }

    /**
     * The value to store, normalised against the options this theme has.
     * THEME_DEFAULT is preserved: here blank is a real choice.
     */
    public static function normalize(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return array_key_exists($value, self::options()) ? $value : self::THEME_DEFAULT;
    }
}
