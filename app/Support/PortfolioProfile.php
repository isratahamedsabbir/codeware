<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Read side of the portfolio theme's content — everything the one-pager shows.
 *
 * All of it is settings-driven. Projects, Experience and Skills used to have
 * admin screens, models and a table each; testimonials did not exist at all.
 * That split was a trap for the owner: the one-pager's sections were edited from
 * two different places, one of which was a separate CRUD screen reachable only
 * from its own sidebar entry, and the sections that most affect whether a visitor
 * hires you were the ones with no screen at all until this refactor.
 *
 * They are ordinary key/value lists now, edited from Admin → Theme Settings next
 * to the hero copy, so they belong in the settings table rather than in a
 * migration each.
 *
 * Two rules make every field safe to leave blank, which is the state every fresh
 * install is in:
 *
 *  1. A blank field is not a default. A missing hero photo is *no* photo (the
 *     template draws a monogram instead of a broken image), and a missing
 *     availability line is no line. Substituting a placeholder string is exactly
 *     the failure this class exists to remove.
 *  2. A list with no usable row is an empty list, and the template hides the
 *     whole section rather than rendering a heading over nothing. An empty
 *     "Education" heading is worse than no heading: it advertises a gap.
 *
 * Nothing here is cached. Every read is a Cache::rememberForever() behind
 * Setting::get() already, and a portfolio page is one request — an extra layer
 * would only add a second thing to invalidate.
 */
class PortfolioProfile
{
    /**
     * The hero, as the one-pager needs it. `photo` is null when unset (draw a
     * monogram) rather than a placeholder URL; `monogram` is precomputed because
     * both the hero and the browser tab want it.
     *
     * The fields carrying a default (role, tagline, availability, resume_label)
     * fall back to a sensible line rather than to null, so a fresh install still
     * reads as a finished page. The rest are null until the owner fills them in.
     *
     * @return array{
     *     name: string,
     *     role: ?string,
     *     tagline: ?string,
     *     availability: ?string,
     *     location: ?string,
     *     email: ?string,
     *     photo: ?string,
     *     monogram: string,
     *     resume_url: ?string,
     *     resume_label: ?string
     * }
     */
    public static function hero(): array
    {
        $name = trim((string) Setting::get('theme_portfolio_name', '')) ?: Setting::get('site_name', config('app.name'));
        $email = trim((string) Setting::get('contact_email', '')) ?: null;

        return [
            'name' => (string) $name,
            // hero_title / hero_tagline are the keys this theme shipped with, kept
            // as-is: renaming them would silently drop the copy an existing
            // install already has saved and fall back to the defaults below.
            'role' => self::text('theme_portfolio_hero_title', 'Full Stack Developer'),
            'tagline' => self::text('theme_portfolio_hero_tagline', 'Building fast, reliable, and scalable web applications with modern tools. Passionate about clean code and thoughtful design.'),
            'availability' => self::text('theme_portfolio_availability', 'Available for new projects'),
            'location' => self::text('theme_portfolio_location'),
            'email' => $email,
            'photo' => self::text('theme_portfolio_photo'),
            'monogram' => static::monogram((string) $name),
            'resume_url' => self::text('theme_portfolio_resume_url'),
            'resume_label' => self::text('theme_portfolio_resume_label', 'Download CV'),
        ];
    }

    /**
     * The hero's trust strip: a value and the label that gives it meaning.
     *
     * "5+" on its own is noise; "5+ Years shipping" is a claim a reader can weigh.
     * The label is therefore part of the row, not decoration, and a row missing
     * either half is dropped — half a stat is worse than one fewer stat.
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public static function stats(): Collection
    {
        return static::rows('theme_portfolio_stats', 'value')
            ->map(fn (array $row) => [
                'value' => $row['value'],
                'label' => $row['label'] ?? '',
            ])
            // Both halves are required: "5+" alone is noise, and a bare label with
            // no figure behind it is worse than leaving the slot out.
            ->filter(fn (array $row) => $row['label'] !== '')
            ->values();
    }

    /**
     * "What I do" — the service cards under the hero.
     *
     * @return Collection<int, array{title: string, description: string, icon: string}>
     */
    public static function services(): Collection
    {
        return static::rows('theme_portfolio_services', 'title')
            ->map(fn (array $row) => [
                'title' => $row['title'],
                'description' => $row['description'] ?? '',
                'icon' => $row['icon'] ?? '',
            ])
            ->values();
    }

    /**
     * Education, as {degree, school, period}.
     *
     * @return Collection<int, array{degree: string, school: string, period: string}>
     */
    public static function education(): Collection
    {
        return static::rows('theme_portfolio_education', 'title')
            ->map(fn (array $row) => [
                'degree' => $row['title'],
                'school' => $row['description'] ?? '',
                'period' => $row['period'] ?? '',
            ])
            ->values();
    }

    /**
     * Certifications, as {title, issuer, date}.
     *
     * @return Collection<int, array{title: string, issuer: string, date: string}>
     */
    public static function certifications(): Collection
    {
        return static::rows('theme_portfolio_certifications', 'title')
            ->map(fn (array $row) => [
                'title' => $row['title'],
                'issuer' => $row['description'] ?? '',
                'date' => $row['period'] ?? '',
            ])
            ->values();
    }

    /**
     * The project rail, as {title, description, icon, tech, stats, link}.
     *
     * `tech` is the one list inside a list. A repeater row is flat — the admin
     * screen hydrates every value to a scalar string — so the owner types the
     * stack as one comma-separated line and it is split back into chips here,
     * rather than the field being a nested repeater the form cannot render.
     *
     * @return Collection<int, array{title: string, description: string, icon: string, tech: array<int, string>, stats: string, link: string}>
     */
    public static function projects(): Collection
    {
        return static::rows('theme_portfolio_projects', 'title')
            ->map(fn (array $row) => [
                'title' => $row['title'],
                'description' => $row['description'] ?? '',
                'icon' => $row['icon'] ?? '',
                'tech' => static::splitList($row['tech'] ?? ''),
                'stats' => $row['stats'] ?? '',
                'link' => $row['link'] ?? '',
            ])
            ->values();
    }

    /**
     * The work timeline, as {role, company, period, description}.
     *
     * @return Collection<int, array{role: string, company: string, period: string, description: string}>
     */
    public static function experiences(): Collection
    {
        return static::rows('theme_portfolio_experiences', 'role')
            ->map(fn (array $row) => [
                'role' => $row['role'],
                'company' => $row['company'] ?? '',
                'period' => $row['period'] ?? '',
                'description' => $row['description'] ?? '',
            ])
            ->values();
    }

    /**
     * The technology section, already grouped for the template: a map of group
     * name to that group's skills, in stored order.
     *
     * Grouped rather than flattened because the section is only legible as a
     * handful of labelled columns — a single 23-item list of technologies tells
     * a visitor nothing about what they would be hired for.
     *
     * A skill with no group is dropped, not filed under a blank heading: the old
     * model made `group` a required column, and a skill the owner could not
     * place is a row they will not notice is missing.
     *
     * @return Collection<string, Collection<int, array{name: string, icon: string, description: string}>>
     */
    public static function skillGroups(): Collection
    {
        return static::rows('theme_portfolio_skills', 'name')
            ->map(fn (array $row) => [
                'name' => $row['name'],
                'group' => $row['group'] ?? '',
                'icon' => $row['icon'] ?? '',
                'description' => $row['description'] ?? '',
            ])
            ->filter(fn (array $row) => $row['group'] !== '')
            ->groupBy('group')
            ->map(fn (Collection $skills) => $skills
                ->map(fn (array $skill) => [
                    'name' => $skill['name'],
                    'icon' => $skill['icon'],
                    'description' => $skill['description'],
                ])
                ->values());
    }

    /**
     * Testimonials, as {quote, name, role, rating}.
     *
     * `rating` is null when the owner left it blank, which is different from
     * zero and means "no stars" — a testimonial section that quietly printed an
     * empty rating row would look like a broken widget. The template draws the
     * stars only when there is something to draw.
     *
     * @return Collection<int, array{quote: string, name: string, role: string, rating: ?int}>
     */
    public static function testimonials(): Collection
    {
        return static::rows('theme_portfolio_testimonials', 'quote')
            ->map(fn (array $row) => [
                'quote' => $row['quote'],
                'name' => $row['name'] ?? '',
                'role' => $row['role'] ?? '',
                'rating' => static::rating($row['rating'] ?? ''),
            ])
            ->values();
    }

    /**
     * Up to two letters for the hero's monogram fallback, taken from the first
     * two words of the name ("Sabbir Hossain" -> "SH").
     *
     * Cased deliberately: a monogram is a logotype, and lowercase initials read
     * as a rendering bug next to a capitalised name.
     */
    public static function monogram(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $letters = '';

        foreach ($words as $word) {
            // Strip anything that is not a letter or digit first, so a name like
            // "3S-Tech" yields "3S" rather than a broken multi-byte character.
            $clean = preg_replace('/[^\p{L}\p{N}]/u', '', $word) ?? '';

            if ($clean === '') {
                continue;
            }

            $letters .= mb_strtoupper(mb_substr($clean, 0, 1));

            if (mb_strlen($letters) === 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : '··';
    }

    /**
     * The rows of a repeating theme field, as stored.
     *
     * The admin writes these as a JSON array of objects (one per repeated field
     * group on the theme settings screen). Every field is optional, so this
     * normalises rather than assumes: a hand-edited or half-migrated value can
     * be an object instead of a list, a list of strings instead of objects, or
     * plain invalid JSON, and none of those may fatal a public page.
     *
     * A JSON *object* is rejected outright rather than read as a one-row list.
     * `{"title": "Freelance"}` is what a hand edit that forgot the outer
     * brackets leaves behind, and reading it as a single row would put a
     * half-filled card on the public page — the operator's in-progress edit,
     * published. The admin screen is the forgiving side of this: it shows such a
     * value as an editable row, so it can be fixed or deleted deliberately.
     *
     * @return Collection<int, array<string, string>>
     */
    private static function rows(string $key, string $identity): Collection
    {
        $raw = trim((string) Setting::get($key, ''));

        if ($raw === '') {
            return collect();
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            return collect();
        }

        return collect($decoded)
            ->map(function ($row) use ($identity) {
                if (is_string($row)) {
                    $row = [$identity => $row];
                }

                if (! is_array($row)) {
                    return null;
                }

                $normalised = [];

                foreach ($row as $field => $value) {
                    if (! is_string($field)) {
                        continue;
                    }

                    $normalised[$field] = is_scalar($value) ? trim((string) $value) : '';
                }

                return $normalised;
            })
            ->filter()
            // The identity field is the row's own claim — a stat without a value,
            // a service without a title. A row with only its supporting fields
            // filled in has nothing to show, so it is not a row.
            ->filter(fn (array $row) => ($row[$identity] ?? '') !== '')
            ->values();
    }

    /**
     * A trimmed string setting, or null when it holds nothing — so a template can
     * use @if rather than checking for ''.
     */
    private static function text(string $key, ?string $default = null): ?string
    {
        $value = trim((string) Setting::get($key, ''));

        if ($value !== '') {
            return $value;
        }

        return $default !== null && trim($default) !== '' ? trim($default) : null;
    }

    /**
     * Split a comma- (or newline-) separated field into clean, non-empty parts.
     *
     * Blank segments are dropped rather than kept as empty chips, because the
     * owner leaves one of these trailing: "Laravel, MySQL," is a normal way to
     * type a list and must not render an empty pill at the end of the stack.
     *
     * @return array<int, string>
     */
    private static function splitList(string $value): array
    {
        $parts = preg_split('/[,\n]/', $value) ?: [];

        return array_values(array_filter(
            array_map(fn (string $part) => trim($part), $parts),
            fn (string $part) => $part !== ''
        ));
    }

    /**
     * A 1-5 star rating, or null when the owner left it blank or typed something
     * that is not a number.
     *
     * Clamped rather than rejected: the value is a free-text field in the admin
     * form, and quietly ignoring a "5" someone typed as "5/5" would be a worse
     * answer than reading the number out of it. Anything with no digits in it
     * is treated as no rating at all.
     */
    private static function rating(string $value): ?int
    {
        if (! preg_match('/\d+/', $value, $match)) {
            return null;
        }

        return max(1, min(5, (int) $match[0]));
    }
}
