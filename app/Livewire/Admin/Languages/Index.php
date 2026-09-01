<?php

namespace App\Livewire\Admin\Languages;

use App\Models\Language;
use App\Models\Setting;
use App\Models\Translation;
use App\Support\AdminActivity;
use App\Support\Locale;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function makeDefault(int $id): void
    {
        $language = Language::findOrFail($id);
        $language->makeDefault();

        // The default language backs the app locale, so keep the setting in step.
        Locale::set($language->code);

        AdminActivity::log('updated', "Language: {$language->name} set as default");
        $this->dispatch('notify', message: __('Default language updated'));
    }

    public function toggleActive(int $id): void
    {
        $language = Language::findOrFail($id);

        if ($language->is_default && $language->is_active) {
            $this->dispatch('notify', message: __('The default language cannot be deactivated'));

            return;
        }

        $language->update(['is_active' => ! $language->is_active]);

        AdminActivity::log('updated', "Language: {$language->name} ".($language->is_active ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: __('Language updated successfully'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'language-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $language = Language::findOrFail($this->deletingId);

            if ($language->is_default) {
                $this->dispatch('notify', message: __('The default language cannot be deleted'));
                $this->deletingId = null;
                $this->dispatch('close-modal', name: 'language-delete');

                return;
            }

            // Its translation rows are orphaned otherwise — `translations.locale` is a plain
            // string column, not a foreign key.
            Translation::where('locale', $language->code)->delete();

            AdminActivity::log('deleted', "Language: {$language->name}");
            $language->delete();
            Translation::flushCache();

            $this->dispatch('notify', message: __('Language deleted successfully'));
            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'language-delete');
    }

    public function render()
    {
        $languages = Language::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('native_name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->ordered()
            ->paginate(Setting::perPage());

        // Counted once for the whole page rather than per row.
        $translated = Translation::query()
            ->translated()
            ->selectRaw('locale, count(distinct `key`) as aggregate')
            ->groupBy('locale')
            ->pluck('aggregate', 'locale');

        return view('livewire.admin.languages.index', [
            'languages'  => $languages,
            'totalKeys'  => Translation::distinct()->count('key'),
            'translated' => $translated,
        ])->layout('layouts.admin', ['title' => __('Languages')]);
    }
}
