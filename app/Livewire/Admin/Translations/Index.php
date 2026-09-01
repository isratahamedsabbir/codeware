<?php

namespace App\Livewire\Admin\Translations;

use App\Models\Language;
use App\Models\Setting;
use App\Models\Translation;
use App\Support\AdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $groupFilter = '';
    public string $filter = 'all';
    public string $localeFilter = '';

    /**
     * Edited cell values keyed by translation row id. Integer keys keep Livewire's dot
     * binding happy — translation keys themselves contain spaces and punctuation.
     *
     * @var array<int, string|null>
     */
    public array $values = [];

    public ?string $deletingKey = null;
    public ?string $deletingGroup = null;

    #[Validate('required|string|max:191')]
    public string $newKey = '';

    public string $newGroup = '*';

    public function mount(): void
    {
        $this->localeFilter = Language::where('is_default', false)->active()->ordered()->value('code')
            ?? Language::active()->ordered()->value('code')
            ?? '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedLocaleFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Persist every cell the admin has touched, across pages.
     */
    public function save(): void
    {
        if ($this->values === []) {
            $this->dispatch('notify', message: __('Nothing to save'));

            return;
        }

        $rows = Translation::whereIn('id', array_keys($this->values))->get()->keyBy('id');
        $changed = 0;

        foreach ($this->values as $id => $value) {
            $row = $rows->get((int) $id);

            if (! $row) {
                continue;
            }

            $value = is_string($value) ? trim($value) : null;
            $value = $value === '' ? null : $value;

            if ($row->value === $value) {
                continue;
            }

            $row->update(['value' => $value]);
            $changed++;
        }

        Translation::flushCache();

        if ($changed === 0) {
            $this->dispatch('notify', message: __('No changes to save'));

            return;
        }

        AdminActivity::log('updated', "Translations: {$changed} string(s) updated");
        $this->dispatch('notify', message: __(':count translation(s) saved', ['count' => $changed]));
    }

    public function addKey(): void
    {
        $this->validateOnly('newKey');

        $key   = trim($this->newKey);
        $group = trim($this->newGroup) ?: '*';

        $exists = Translation::where('group', $group)->where('key', $key)->exists();

        if ($exists) {
            $this->addError('newKey', __('That key already exists.'));

            return;
        }

        $default = Language::where('is_default', true)->value('code');

        foreach (Language::pluck('code') as $locale) {
            Translation::create([
                'group'  => $group,
                'key'    => $key,
                'locale' => $locale,
                'value'  => $locale === $default ? $key : null,
            ]);
        }

        Translation::flushCache();
        AdminActivity::log('created', "Translation key: {$key}");

        $this->reset('newKey');
        $this->newGroup = '*';
        $this->dispatch('close-modal', name: 'translation-add');
        $this->dispatch('notify', message: __('Translation key added'));
    }

    public function confirmDelete(string $group, string $key): void
    {
        $this->deletingGroup = $group;
        $this->deletingKey   = $key;
        $this->dispatch('open-modal', name: 'translation-delete');
    }

    public function delete(): void
    {
        if ($this->deletingKey !== null) {
            Translation::where('group', $this->deletingGroup)
                ->where('key', $this->deletingKey)
                ->delete();

            Translation::flushCache();
            AdminActivity::log('deleted', "Translation key: {$this->deletingKey}");

            $this->dispatch('notify', message: __('Translation key deleted'));
            $this->deletingKey = $this->deletingGroup = null;
        }

        $this->dispatch('close-modal', name: 'translation-delete');
    }

    /**
     * Pull newly added __() strings out of the codebase and into the table.
     */
    public function scan(): void
    {
        Artisan::call('translations:scan');

        $output = trim(Artisan::output());

        AdminActivity::log('updated', 'Translations: scanned source for new keys');
        $this->dispatch('notify', message: __('Scan complete. New keys have been imported.'));

        // Surface the command summary for anyone watching the browser console.
        $this->dispatch('translations-scanned', output: $output);
    }

    public function render()
    {
        $locales = Language::active()->ordered()->get();

        if ($this->localeFilter === '' && $locales->isNotEmpty()) {
            $this->localeFilter = $locales->first()->code;
        }

        $rows = $this->baseQuery()
            ->select('group', 'key')
            ->groupBy('group', 'key')
            ->orderBy('key')
            ->paginate(Setting::perPage());

        $this->ensureRows($rows->getCollection(), $locales->pluck('code'));

        $cells = $this->cellsFor($rows->getCollection());

        // Hydrate any cell the admin has not edited yet, leaving existing edits intact.
        foreach ($cells as $byLocale) {
            foreach ($byLocale as $cell) {
                if (! array_key_exists($cell->id, $this->values)) {
                    $this->values[$cell->id] = $cell->value;
                }
            }
        }

        return view('livewire.admin.translations.index', [
            'rows'    => $rows,
            'cells'   => $cells,
            'locales' => $locales,
            'groups'  => Translation::distinct()->orderBy('group')->pluck('group'),
            'stats'   => $this->stats($locales),
        ])->layout('layouts.admin', ['title' => __('Translations')]);
    }

    protected function baseQuery(): Builder
    {
        return Translation::query()
            ->when($this->groupFilter !== '', fn (Builder $q) => $q->where('group', $this->groupFilter))
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('key', 'like', "%{$this->search}%")
                ->orWhere('value', 'like', "%{$this->search}%")))
            ->when(
                in_array($this->filter, ['translated', 'untranslated'], true) && $this->localeFilter !== '',
                fn (Builder $q) => $q->whereExists(fn ($sub) => $sub
                    ->from('translations as t2')
                    ->whereColumn('t2.key', 'translations.key')
                    ->whereColumn('t2.group', 'translations.group')
                    ->where('t2.locale', $this->localeFilter)
                    ->when(
                        $this->filter === 'untranslated',
                        fn ($sub) => $sub->where(fn ($w) => $w->whereNull('t2.value')->orWhere('t2.value', '')),
                        fn ($sub) => $sub->whereNotNull('t2.value')->where('t2.value', '!=', ''),
                    )),
            );
    }

    /**
     * Guarantee a row exists for every key/locale pair on this page, so each cell has an
     * id to bind to. Covers languages added before this screen learned to seed keys.
     *
     * @param  Collection<int, Translation>  $rows
     * @param  Collection<int, string>  $codes
     */
    protected function ensureRows(Collection $rows, Collection $codes): void
    {
        if ($rows->isEmpty() || $codes->isEmpty()) {
            return;
        }

        $existing = Translation::query()
            ->whereIn('key', $rows->pluck('key'))
            ->whereIn('locale', $codes)
            ->get(['group', 'key', 'locale'])
            ->map(fn ($row) => "{$row->group}|{$row->key}|{$row->locale}")
            ->flip();

        $missing = [];

        foreach ($rows as $row) {
            foreach ($codes as $code) {
                if (! $existing->has("{$row->group}|{$row->key}|{$code}")) {
                    $missing[] = [
                        'group'      => $row->group,
                        'key'        => $row->key,
                        'locale'     => $code,
                        'value'      => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if ($missing !== []) {
            Translation::insertOrIgnore($missing);
        }
    }

    /**
     * Cells for the current page indexed as [group|key][locale] => Translation.
     *
     * @param  Collection<int, Translation>  $rows
     * @return Collection<string, Collection<string, Translation>>
     */
    protected function cellsFor(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        return Translation::query()
            ->whereIn('key', $rows->pluck('key'))
            ->get()
            ->groupBy(fn (Translation $row) => "{$row->group}|{$row->key}")
            ->map(fn (Collection $group) => $group->keyBy('locale'));
    }

    /**
     * Per-locale completion counts for the summary strip.
     *
     * @param  Collection<int, Language>  $locales
     * @return array<string, array{translated: int, total: int, percent: int}>
     */
    protected function stats(Collection $locales): array
    {
        $total = Translation::distinct()->count('key');

        $translated = Translation::query()
            ->translated()
            ->selectRaw('locale, count(distinct `key`) as aggregate')
            ->groupBy('locale')
            ->pluck('aggregate', 'locale');

        return $locales->mapWithKeys(fn (Language $language) => [
            $language->code => [
                'translated' => (int) ($translated[$language->code] ?? 0),
                'total'      => $total,
                'percent'    => $total > 0 ? (int) round(((int) ($translated[$language->code] ?? 0)) / $total * 100) : 0,
            ],
        ])->all();
    }
}
