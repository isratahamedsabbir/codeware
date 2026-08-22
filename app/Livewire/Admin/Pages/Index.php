<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $pageId) {
            Page::where('id', $pageId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function openPuckEditor(int $pageId): void
    {
        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken('puck-builder', ['*'], now()->addMinutes(config('app.puck_session', 5)))->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/page/{$pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'page-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $page = Page::findOrFail($this->deletingId);
            PageCascade::deleteEntityFor($page);
            AdminActivity::log('deleted', "Page #{$page->id}: {$page->title}");
            $page->delete();
            $this->dispatch('notify', message: 'Page deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'page-delete');
    }

    public function render()
    {
        return view('livewire.admin.pages.index', [
            'pages' => Page::query()
                ->where('type', 'page')
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('title->en', 'like', "%{$this->search}%")
                        ->orWhere('title->bn', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                }))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(30),
        ])->layout('layouts.admin', ['title' => 'Pages']);
    }
}
