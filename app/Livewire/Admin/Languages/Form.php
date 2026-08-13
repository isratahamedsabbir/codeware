<?php

namespace App\Livewire\Admin\Languages;

use App\Models\Language;
use App\Models\Translation;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $languageId = null;

    #[Validate('required|string|max:10|regex:/^[a-zA-Z]{2,3}(?:[_-][a-zA-Z0-9]{2,4})?$/')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $native_name = '';

    #[Validate('required|in:ltr,rtl')]
    public string $direction = 'ltr';

    #[Validate('nullable|string|max:8')]
    public string $flag = '';

    public bool $is_active = true;

    #[Validate('integer|min:0|max:9999')]
    public int $sort_order = 0;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $language = Language::findOrFail($id);

            $this->languageId  = $id;
            $this->code        = $language->code;
            $this->name        = $language->name;
            $this->native_name = $language->native_name ?? '';
            $this->direction   = $language->direction;
            $this->flag        = $language->flag ?? '';
            $this->is_active   = $language->is_active;
            $this->sort_order  = $language->sort_order;
        } else {
            $this->sort_order = (int) Language::max('sort_order') + 1;
        }
    }

    public function save(): void
    {
        $this->code = strtolower(str_replace('_', '-', trim($this->code)));

        $rules = $this->getRules();
        $rules['code'] .= $this->languageId
            ? '|unique:languages,code,'.$this->languageId
            : '|unique:languages,code';

        $this->validate($rules);

        $creating = $this->languageId === null;
        $existing = $creating ? null : Language::findOrFail($this->languageId);

        $data = [
            'code'        => $this->code,
            'name'        => $this->name,
            'native_name' => $this->native_name ?: null,
            'direction'   => $this->direction,
            'flag'        => $this->flag ?: null,
            // The default language must stay active regardless of the toggle.
            'is_active'   => $this->is_active || (bool) $existing?->is_default,
            'sort_order'  => $this->sort_order,
        ];

        if ($existing) {
            $existing->update($data);
            $language = $existing;
        } else {
            $language = Language::create($data);
        }

        if ($creating) {
            // Give the new language a blank row for every existing key so it shows up in
            // the translation editor straight away.
            $this->seedKeysFor($language->code);
        }

        AdminActivity::log($creating ? 'created' : 'updated', "Language: {$language->name}");

        $this->dispatch('notify', message: $creating
            ? __('Language created successfully')
            : __('Language updated successfully'));

        $this->redirect(route('admin.languages'), navigate: true);
    }

    /**
     * Create an empty translation row per existing key for a newly added language.
     */
    protected function seedKeysFor(string $locale): void
    {
        $existing = Translation::where('locale', $locale)->pluck('key')->all();

        $rows = Translation::query()
            ->select('group', 'key')
            ->distinct()
            ->whereNotIn('key', $existing)
            ->get()
            ->map(fn (Translation $row) => [
                'group'      => $row->group,
                'key'        => $row->key,
                'locale'     => $locale,
                'value'      => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Translation::insertOrIgnore($chunk);
        }

        Translation::flushCache($locale);
    }

    public function render()
    {
        return view('livewire.admin.languages.form')
            ->layout('layouts.admin', [
                'title' => $this->languageId ? __('Edit Language') : __('New Language'),
            ]);
    }
}
