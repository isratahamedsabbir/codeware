<?php

namespace App\Livewire\Admin\Applications;

use App\Models\Job;
use App\Models\JobApplication;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $jobFilter = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedJobFilter(): void { $this->resetPage(); }

    #[Computed]
    public function jobs()
    {
        return Job::orderBy('title->en')->get();
    }

    public function updateStatus(int $id, string $status): void
    {
        $application = JobApplication::findOrFail($id);
        $application->update(['status' => $status]);
        $this->dispatch('notify', message: 'Status updated successfully');
    }

    public function render()
    {
        return view('livewire.admin.applications.index', [
            'applications' => JobApplication::query()
                ->with('job.department')
                ->when($this->search, fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->jobFilter, fn ($q) => $q->where('job_id', $this->jobFilter))
                ->latest()
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Applications']);
    }
}
