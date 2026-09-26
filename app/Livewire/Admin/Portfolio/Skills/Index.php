<?php

namespace App\Livewire\Admin\Portfolio\Skills;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\PortfolioSkill;
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
        foreach ($order as $sortOrder => $skillId) {
            PortfolioSkill::where('id', $skillId)->update(['sort_order' => $sortOrder]);
        }

        // A query-builder mass update doesn't fire the model's `saved` event,
        // so the shared content cache wouldn't otherwise notice the new order.
        ContentCache::bust();
    }

    public function toggleStatus(int $id): void
    {
        $skill = PortfolioSkill::findOrFail($id);
        $newStatus = $skill->status === 'active' ? 'inactive' : 'active';

        $skill->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Portfolio Skill: {$skill->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Skill status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'skill-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $skill = PortfolioSkill::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Portfolio Skill: {$skill->name}");
            $skill->delete();
            $this->dispatch('notify', message: 'Skill deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'skill-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'skill-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $skills = PortfolioSkill::whereIn('id', $this->selectedIds)->get();

        foreach ($skills as $skill) {
            AdminActivity::log('deleted', "Portfolio Skill: {$skill->name}");
            $skill->delete();
        }

        $count = $skills->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('skill', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'skill-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.portfolio.skills.index', [
            'skills' => PortfolioSkill::query()
                ->with('creator')
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhere('group', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Skills', 'hidePageHeading' => true]);
    }
}
