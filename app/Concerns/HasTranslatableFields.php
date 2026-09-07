<?php

namespace App\Concerns;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;

/**
 * Powers the dynamic per-locale tabs (see <x-admin-locale-tabs>) on every
 * admin Form component backing a spatie/laravel-translatable model. Each
 * translatable field is a single array property keyed by locale code (e.g.
 * `public array $name = [];`, bound via `wire:model="name.{{ $code }}"`)
 * instead of one flat property per hardcoded locale — so adding/removing a
 * language in /admin/languages changes which tabs render with no code
 * changes here.
 */
trait HasTranslatableFields
{
    #[Computed]
    public function primaryLocale(): string
    {
        return Locale::primary();
    }

    /**
     * Loads every saved locale for each field — including locales since
     * deactivated/removed from /admin/languages, so that data is preserved
     * (just not rendered) rather than lost if the language is re-added later.
     */
    protected function hydrateTranslatable(Model $model, array $fields): void
    {
        foreach ($fields as $field) {
            $this->$field = $model->getTranslations($field);
        }
    }

    /**
     * The primary (default) locale's value for a translatable field — used
     * for slug generation and activity-log messages in place of the old
     * flat $this->{field}_en references.
     */
    protected function primaryValue(string $field): string
    {
        return $this->$field[$this->primaryLocale] ?? '';
    }

    /**
     * True when a Livewire updated() dot-path (e.g. "name.en") is a change to
     * the primary locale's value for $field — Livewire's magic
     * updated{Field}() hooks never fire for array sub-key mutations, so this
     * is how per-field "regenerate the slug as the admin types" logic hooks
     * back in from a component's own updated() catch-all.
     */
    protected function isPrimaryLocaleUpdate(string $property, string $field): bool
    {
        return $property === "{$field}.{$this->primaryLocale}";
    }

    /**
     * Per-active-locale validation rules for a translatable field — required
     * only for the primary locale, keeping the other base rules but dropping
     * "required" for every other active locale. Inactive locales get no rule
     * at all, since their inputs aren't rendered.
     *
     * @param  array<string, string|array<int, string>>  $fieldRules  e.g. ['name' => 'required|string|max:255']
     * @return array<string, array<int, string>>
     */
    protected function translatableRules(array $fieldRules): array
    {
        $primary = $this->primaryLocale;

        // Always cover the primary locale, even if it isn't (yet) reflected
        // in the languages table — e.g. before an admin has ever visited
        // /admin/languages, Locale::codes() is empty, but the app still has
        // to enforce the primary field as required.
        $codes = Locale::codes();
        if (! in_array($primary, $codes, true)) {
            $codes[] = $primary;
        }

        $rules = [];

        foreach ($fieldRules as $field => $base) {
            $base = is_string($base) ? explode('|', $base) : $base;
            $optional = array_values(array_diff($base, ['required']));

            if (! in_array('nullable', $optional, true)) {
                array_unshift($optional, 'nullable');
            }

            foreach ($codes as $code) {
                $rules["{$field}.{$code}"] = $code === $primary ? $base : $optional;
            }
        }

        return $rules;
    }

    /**
     * Shapes an array-typed translatable field for a create()/update()
     * payload, dropping empty values — mirrors the old
     * array_filter(['en' => ..., 'bn' => ...]) calls, but naturally carries
     * forward any already-saved inactive-locale values untouched.
     */
    protected function translatablePayload(string $field): array
    {
        return array_filter($this->$field);
    }
}
