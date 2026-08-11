<?php

namespace App\Livewire\Admin\Jobs;

use App\Models\Job;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $jobId) {
            Job::where('id', $jobId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'job-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Job::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'Job deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'job-delete');
    }

    public function render()
    {
        return view('livewire.admin.jobs.index', [
            'jobs' => Job::query()
                ->with('department')
                ->when($this->search, fn ($q) => $q
                    ->where('title->en', 'like', "%{$this->search}%")
                    ->orWhere('title->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->withCount('applications')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Jobs']);
    }
}
