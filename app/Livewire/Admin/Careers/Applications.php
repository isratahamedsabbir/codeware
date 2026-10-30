<?php

namespace App\Livewire\Admin\Careers;

use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Read-only inbox of career applications; the only edit is the review status. */
class Applications extends Component
{
    use HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    #[Url(as: 'job')]
    public string $jobFilter = '';

    public ?int $viewingId = null;

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedJobFilter(): void
    {
        $this->resetPage();
    }

    public function view(int $id): void
    {
        $this->viewingId = $this->viewingId === $id ? null : $id;
    }

    public function setStatus(int $id, string $status): void
    {
        if (! in_array($status, JobApplication::STATUSES, true)) {
            return;
        }

        $application = JobApplication::findOrFail($id);
        $application->update(['status' => $status]);
        AdminActivity::log('updated', "Application #{$application->id} ({$application->name}) → {$status}");
        $this->dispatch('notify', message: 'Status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'application-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $application = JobApplication::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Application #{$application->id} ({$application->name})");
            Storage::disk('local')->delete($application->resume_path);
            $application->delete();
            $this->dispatch('notify', message: 'Application deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'application-delete');
    }

    public function render()
    {
        return view('livewire.admin.careers.applications', [
            'applications' => JobApplication::query()
                ->with('jobPost')
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->jobFilter, fn ($q) => $q->where('job_post_id', $this->jobFilter))
                ->latest()
                ->paginate($this->perPage),
            'jobs' => JobPost::withTrashed()->orderBy('title')->get(['id', 'title']),
        ])->layout('layouts.admin', ['title' => 'Applications', 'hidePageHeading' => true]);
    }
}
