<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Settings → Custom Code. The two original textareas (custom_head_code /
 * custom_body_code) are the "Global" code, output on every storefront page.
 *
 * On top of that, `custom_code_rules` holds any number of named snippets
 * (name + head + body). A snippet does nothing by itself: a page, product,
 * post or category opts in by picking it from the "Custom code" dropdown in its
 * SEO section, which stores the snippet's id on its paired Page.
 */
class CustomCode
{
    public const SETTING = 'custom_code_rules';

    /** @return array<int, array{id: string, name: string, head: string, body: string}> */
    public static function rules(): array
    {
        $rules = json_decode((string) Setting::get(self::SETTING, '[]'), true);

        return collect(is_array($rules) ? $rules : [])
            ->filter(fn ($rule) => is_array($rule))
            ->map(fn (array $rule) => [
                'id' => (string) ($rule['id'] ?? '') ?: self::newId(),
                'name' => (string) ($rule['name'] ?? ''),
                'head' => (string) ($rule['head'] ?? ''),
                'body' => (string) ($rule['body'] ?? ''),
            ])
            ->values()
            ->all();
    }

    public static function newId(): string
    {
        return Str::lower(Str::random(12));
    }

    /** @return array<string, string> snippet id => name, for the page dropdowns */
    public static function options(): array
    {
        return collect(self::rules())
            ->mapWithKeys(fn (array $rule) => [$rule['id'] => $rule['name'] !== '' ? $rule['name'] : 'Untitled'])
            ->all();
    }

    /** Global code first, then the snippet the current page picked, if any. */
    public static function render(string $position, ?Page $page = null): string
    {
        $key = $position === 'head' ? 'head' : 'body';

        $parts = [(string) Setting::get("custom_{$key}_code")];

        if ($page?->custom_code_id) {
            foreach (self::rules() as $rule) {
                if ($rule['id'] === $page->custom_code_id) {
                    $parts[] = $rule[$key];
                }
            }
        }

        return implode("\n", array_filter($parts, fn ($code) => filled($code)));
    }
}
