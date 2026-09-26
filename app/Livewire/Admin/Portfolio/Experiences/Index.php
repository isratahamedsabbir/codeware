<?php

namespace App\Livewire\Admin\Portfolio\Experiences;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\PortfolioExperience;
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
        foreach ($order as $sortOrder => $experienceId) {
            PortfolioExperience::where('id', $experienceId)->update(['sort_order' => $sortOrder]);
        }

        // A query-builder mass update doesn't fire the model's `saved` event,
        // so the shared content cache wouldn't otherwise notice the new order.
        ContentCache::bust();
    }

    public function toggleStatus(int $id): void
    {
        $experience = PortfolioExperience::findOrFail($id);
        $newStatus = $experience->status === 'active' ? 'inactive' : 'active';

        $experience->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Portfolio Experience: {$experience->role} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Experience status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'experience-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $experience = PortfolioExperience::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Portfolio Experience: {$experience->role}");
            $experience->delete();
            $this->dispatch('notify', message: 'Experience deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'experience-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'experience-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $experiences = PortfolioExperience::whereIn('id', $this->selectedIds)->get();

        foreach ($experiences as $experience) {
            AdminActivity::log('deleted', "Portfolio Experience: {$experience->role}");
            $experience->delete();
        }

        $count = $experiences->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('experience', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'experience-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.portfolio.experiences.index', [
            'experiences' => PortfolioExperience::query()
                ->with('creator')
                ->when($this->search, fn ($q) => $q->where('role->en', 'like', "%{$this->search}%")
                    ->orWhere('role->bn', 'like', "%{$this->search}%")
                    ->orWhere('company->en', 'like', "%{$this->search}%")
                    ->orWhere('company->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Experience', 'hidePageHeading' => true]);
    }
}
