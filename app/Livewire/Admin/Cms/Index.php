<?php

namespace App\Livewire\Admin\Cms;

use App\Models\CmsSection;
use App\Models\Page;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public int $pageId;

    public string $search = '';

    public ?int $deletingId = null;

    public function mount(int $pageId): void
    {
        $this->pageId = Page::findOrFail($pageId)->id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $cmsId) {
            CmsSection::where('id', $cmsId)->where('page_id', $this->pageId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function toggleStatus(int $id): void
    {
        $cms = CmsSection::findOrFail($id);
        $newStatus = $cms->status === 'active' ? 'inactive' : 'active';

        $cms->update(['status' => $newStatus]);

        AdminActivity::log('updated', "CMS section: {$cms->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
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
            AdminActivity::log('deleted', "CMS section: {$cms->name}");
            $cms->delete();
            $this->dispatch('notify', message: 'CMS section deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'cms-delete');
    }

    public function render()
    {
        return view('livewire.admin.cms.index', [
            'page' => Page::findOrFail($this->pageId),
            'sections' => CmsSection::query()
                ->where('page_id', $this->pageId)
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(30),
        ])->layout('layouts.admin', ['title' => 'CMS']);
    }
}
