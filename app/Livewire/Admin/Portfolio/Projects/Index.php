<?php

namespace App\Livewire\Admin\Portfolio\Projects;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\PortfolioProject;
use App\Support\AdminActivity;
use App\Support\ContentCache;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $projectId) {
            PortfolioProject::where('id', $projectId)->update(['sort_order' => $sortOrder]);
        }

        // A query-builder mass update doesn't fire the model's `saved` event,
        // so the shared content cache wouldn't otherwise notice the new order.
        ContentCache::bust();
    }

    public function toggleStatus(int $id): void
    {
        $project = PortfolioProject::findOrFail($id);
        $newStatus = $project->status === 'active' ? 'inactive' : 'active';

        $project->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Portfolio Project: {$project->title} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Project status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'project-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $project = PortfolioProject::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Portfolio Project: {$project->title}");
            $project->delete();
            $this->dispatch('notify', message: 'Project deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'project-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'project-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $projects = PortfolioProject::whereIn('id', $this->selectedIds)->get();

        foreach ($projects as $project) {
            AdminActivity::log('deleted', "Portfolio Project: {$project->title}");
            $project->delete();
        }

        $count = $projects->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('project', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'project-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.portfolio.projects.index', [
            'projects' => PortfolioProject::query()
                ->with('creator')
                ->when($this->search, fn ($q) => $q->where('title->en', 'like', "%{$this->search}%")
                    ->orWhere('title->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Projects', 'hidePageHeading' => true]);
    }
}
