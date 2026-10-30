<?php

namespace App\Livewire\Admin\Careers;

use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\JobPost;
use App\Support\AdminActivity;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Job posts for the storefront Careers page: list + create/edit modal.
 * Applications they receive are handled in Careers\Applications.
 */
class Index extends Component
{
    use HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $title = '';

    public string $department = '';

    public string $location = '';

    public string $employment_type = 'full-time';

    public int $vacancy = 1;

    public ?string $deadline = null;

    public string $description = '';

    public string $requirements = '';

    public string $status = 'draft';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'employment_type' => ['required', Rule::in(array_keys(JobPost::EMPLOYMENT_TYPES))],
            'vacancy' => 'required|integer|min:1|max:65000',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string|max:20000',
            'requirements' => 'nullable|string|max:20000',
            'status' => ['required', Rule::in(JobPost::STATUSES)],
        ];
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'title', 'department', 'location', 'employment_type', 'vacancy', 'deadline', 'description', 'requirements', 'status']);
        $this->dispatch('open-modal', name: 'job-form');
    }

    public function edit(int $id): void
    {
        $job = JobPost::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $job->id;
        $this->title = $job->title;
        $this->department = (string) $job->department;
        $this->location = (string) $job->location;
        $this->employment_type = $job->employment_type;
        $this->vacancy = $job->vacancy;
        $this->deadline = $job->deadline?->toDateString();
        $this->description = (string) $job->description;
        $this->requirements = (string) $job->requirements;
        $this->status = $job->status;
        $this->dispatch('open-modal', name: 'job-form');
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['deadline'] = $data['deadline'] ?: null;
        foreach (['department', 'location', 'description', 'requirements'] as $key) {
            $data[$key] = filled($data[$key]) ? $data[$key] : null;
        }

        if ($this->editingId) {
            $job = JobPost::findOrFail($this->editingId);
            $job->update($data);
            AdminActivity::log('updated', "Job post: {$job->title}");
        } else {
            $job = JobPost::create($data);
            AdminActivity::log('created', "Job post: {$job->title}");
        }

        $this->dispatch('notify', message: 'Job post saved successfully');
        $this->dispatch('close-modal', name: 'job-form');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'job-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $job = JobPost::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Job post: {$job->title}");
            $job->delete();
            $this->dispatch('notify', message: 'Job post deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'job-delete');
    }

    public function render()
    {
        return view('livewire.admin.careers.index', [
            'jobs' => JobPost::query()
                ->withCount('applications')
                ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Job Posts', 'hidePageHeading' => true]);
    }
}
