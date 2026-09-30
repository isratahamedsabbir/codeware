<?php

namespace App\Support;

/**
 * The panel's typeface: a choice among three faces, two of which the machine
 * already has.
 *
 * The split that matters here is between an option the OS answers and an option
 * it cannot. SYSTEM and SEGOE are resolved by the browser from what is
 * installed, so the whole setting is a change to one CSS custom property and
 * costs no request. ROBOTO is a webfont: not installed on Windows or macOS by
 * default, so selecting it means shipping a woff2 and a @font-face, and the
 * stack has to keep a system fallback behind the family name in case the file
 * never arrives.
 *
 * That asymmetry is why this class also describes the file for the webfont
 * option (see preloadFor() and facesFor()) instead of leaving the layout to
 * hardcode a path. Adding a second webfont later is a matter of describing it
 * here, not of editing a view.
 *
 *   - SYSTEM lets the OS answer: San Francisco on a Mac, Segoe UI on Windows,
 *     whatever the Linux desktop has. The default, and the right one to hand to
 *     someone who has never thought about panel typography — native is the point.
 *   - SEGOE puts Segoe UI first whatever the OS is. On Windows the same face the
 *     system option resolves to; elsewhere it is the difference between a panel
 *     that looks like the machine it is on and one that looks like it was
 *     designed on a different one. It is here for exactly that case, and because
 *     the panel used to be hard-coded to it.
 *   - ROBOTO is Google's most widely recognised UI face and the one most teams
 *     have a mockup or a design system drawn in. It is the only option here
 *     that downloads anything.
 *
 * @see resources/views/layouts/admin.blade.php, which applies the stack to
 *      --font-sans and inlines the @font-face for whichever option needs one.
 */
class AdminFont
{
    /**
     * The null option's slug: the OS's own UI font, whatever that is.
     *
     * A theme folder and a settings group are never called this, so the value
     * cannot collide with one.
     */
    public const SYSTEM = 'system';

    /**
     * Segoe UI named first, ahead of the OS's answer.
     */
    public const SEGOE = 'segoe';

    /**
     * A webfont, self-hosted. See the file map in facesFor().
     */
    public const ROBOTO = 'roboto';

    /**
     * The stack for each option, keyed by stored value.
     *
     * Every one ends in the same generic tail, so text still renders on a
     * machine with none of the named faces — a stack that could resolve to
     * nothing would leave the panel to the browser's default instead, which is
     * not one of these.
     *
     * Roboto leads its own stack, not the other way round: the whole point of
     * choosing it over the system option is that it wins where it is installed,
     * and it also has to win over a same-named face that some other tool may
     * have registered. The system tail behind it is the fallback for the case
     * where the woff2 has not arrived.
     *
     * @return array<string, string>
     */
    public static function stacks(): array
    {
        return [
            self::SYSTEM => "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            self::SEGOE => "'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            self::ROBOTO => "'Roboto', 'Helvetica Neue', Arial, sans-serif",
        ];
    }

    /**
     * The label each stored value is offered under, in the order they are offered.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::SYSTEM => 'System font',
            self::SEGOE => 'Segoe UI',
            self::ROBOTO => 'Roboto',
        ];
    }

    /**
     * The @font-face sources for an option, keyed by stored value.
     *
     * Only the webfont options appear. A system face has no file, so declaring
     * one would be inventing a download.
     *
     * Self-hosted rather than linked to a CDN, for the reasons laid out at the
     * top of resources/css/fonts.css: a third-party stylesheet is
     * render-blocking and costs a connection setup on every panel load. The
     * declarations are inlined into the layout only when their option is
     * selected, so an unselected webfont costs nothing at all.
     *
     * The subsets are Google's own unicode ranges, copied rather than invented.
     * They are the mechanism that keeps a file out of the critical path: a page
     * with no character outside a range never downloads the file for that range,
     * so the ~29 KB latin-ext file is spent only by the pages that actually have
     * a character in it. Bengali is deliberately absent — the panel can be
     * translated (see lang/bn.json), and those characters fall through to a
     * system Bengali face rather than bloating the common case for them.
     *
     * font-weight is the variable axis, so one file per subset serves every
     * weight the panel uses, and font-display: swap means text paints in the
     * fallback immediately rather than waiting on the file.
     *
     * @return array<string, array<int, array{url: string, unicodeRange: string}>>
     */
    public static function facesFor(mixed $value): array
    {
        $value = self::normalize($value);

        if ($value !== self::ROBOTO) {
            return [];
        }

        return [
            $value => [
                [
                    'url' => '/fonts/roboto-latin-ext.woff2',
                    'unicodeRange' => 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF',
                ],
                [
                    'url' => '/fonts/roboto-latin.woff2',
                    'unicodeRange' => 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD',
                ],
            ],
        ];
    }

    /**
     * The file to preload for an option, or null when it has none.
     *
     * Only ever the *latin* subset. Preloading a subset the page may not use is
     * how the preloading turns into a speculative download: the browser has to
     * be told to skip the fetch when unicode-range would not have matched, and
     * until it does, the file is a request the preloader cannot cancel. The
     * latin range covers ordinary panel text, so preloading it is a safe bet;
     * latin-ext is left to the range check that happens anyway.
     *
     * @return non-empty-string|null
     */
    public static function preloadFor(mixed $value): ?string
    {
        return self::facesFor($value)[self::ROBOTO][1]['url'] ?? null;
    }

    /**
     * The stack to apply for a stored setting value.
     *
     * An unknown value resolves to the system stack rather than to an unstyled
     * family name. A settings row can be hand-edited, or left over from a
     * version that offered different choices, and a font-family of something
     * that resolves to nothing would silently drop the panel to the browser
     * default instead of the deliberate default.
     *
     * @return non-empty-string
     */
    public static function stackFor(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return self::stacks()[$value] ?? self::stacks()[self::SYSTEM];
    }

    /**
     * The value to store, normalised against the options that exist.
     *
     * Saving goes through this so a stored value is always one the select
     * actually offers, and so facesFor() below can rely on it.
     */
    public static function normalize(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return array_key_exists($value, self::options()) ? $value : self::SYSTEM;
    }
}
