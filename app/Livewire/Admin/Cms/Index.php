<?php

namespace App\Livewire\Admin\Cms;

use App\Models\CmsSection;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $themeFilter = '';

    public string $pageFilter = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedThemeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPageFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $cms = CmsSection::findOrFail($id);
        $newStatus = $cms->status === 'active' ? 'inactive' : 'active';

        $cms->update(['status' => $newStatus]);

        AdminActivity::log('updated', "CMS section: {$cms->theme} / {$cms->page} / {$cms->section} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'CMS section status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'cms-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $cms = CmsSection::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "CMS section: {$cms->theme} / {$cms->page} / {$cms->section}");
            $cms->delete();
            $this->dispatch('notify', message: 'CMS section deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'cms-delete');
    }

    public function render()
    {
        return view('livewire.admin.cms.index', [
            'sections' => CmsSection::query()
                ->when($this->themeFilter, fn ($q) => $q->where('theme', $this->themeFilter))
                ->when($this->pageFilter, fn ($q) => $q->where('page', $this->pageFilter))
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('page', 'like', "%{$this->search}%")
                        ->orWhere('section', 'like', "%{$this->search}%");
                }))
                ->orderBy('id')
                ->paginate(30),
            'themes' => \App\Support\Themes::all(),
            'pages' => CmsSection::query()->distinct()->orderBy('page')->pluck('page'),
        ])->layout('layouts.admin', ['title' => 'CMS']);
    }
}
