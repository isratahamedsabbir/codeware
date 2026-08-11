<?php

namespace App\Livewire\Admin\Jobs;

use App\Models\Department;
use App\Models\Job;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $jobId = null;

    #[Validate('required|string|max:255')]
    public string $title_en = '';

    #[Validate('nullable|string|max:255')]
    public string $title_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('nullable|integer|exists:departments,id')]
    public ?int $department_id = null;

    #[Validate('required|string|max:255')]
    public string $position = '';

    #[Validate('required|integer|min:1')]
    public int $vacancy = 1;

    #[Validate('nullable|date')]
    public string $deadline = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('in:active,inactive')]
    public string $status = 'inactive';

    public int $sort_order = 0;

    public ?string $image = null;

    public string $imagePickerId = '';

    public ?string $document_file = null;

    public string $documentPickerId = '';

    #[Validate('nullable|string')]
    public string $description_en = '';

    #[Validate('nullable|string')]
    public string $description_bn = '';

    public function mount(?int $id = null): void
    {
        $this->imagePickerId = 'job-image-picker-' . Str::uuid()->toString();
        $this->documentPickerId = 'job-document-picker-' . Str::uuid()->toString();

        if ($id) {
            $job = Job::findOrFail($id);
            $this->jobId         = $id;
            $this->title_en      = $job->getTranslation('title', 'en', false) ?? '';
            $this->title_bn      = $job->getTranslation('title', 'bn', false) ?? '';
            $this->slug          = $job->slug;
            $this->department_id = $job->department_id;
            $this->position      = $job->position;
            $this->vacancy       = $job->vacancy;
            $this->deadline      = $job->deadline?->format('Y-m-d') ?? '';
            $this->location      = $job->location ?? '';
            $this->status         = $job->status;
            $this->sort_order     = $job->sort_order;
            $this->image          = $job->image ?? null;
            $this->document_file  = $job->document_file ?? null;
            $this->description_en = $job->getTranslation('description', 'en', false) ?? '';
            $this->description_bn = $job->getTranslation('description', 'bn', false) ?? '';
        }
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name->en')->get();
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->title_en) {
            $this->slug = Str::slug($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->jobId
            ? 'required|string|max:255|unique:career_jobs,slug,' . $this->jobId
            : 'required|string|max:255|unique:career_jobs,slug';

        $this->validate($rules);

        $data = [
            'title'         => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
            'slug'          => $this->slug,
            'image'         => $this->image ?: null,
            'document_file' => $this->document_file ?: null,
            'department_id' => $this->department_id,
            'position'      => $this->position,
            'vacancy'       => $this->vacancy,
            'deadline'      => $this->deadline ?: null,
            'location'      => $this->location ?: null,
            'status'        => $this->status,
            'sort_order'    => $this->sort_order,
            'description'   => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
        ];

        if ($this->jobId) {
            Job::findOrFail($this->jobId)->update($data);
            $this->dispatch('notify', message: 'Job updated successfully');
        } else {
            Job::create($data);
            $this->dispatch('notify', message: 'Job created successfully');
        }

        $this->redirect(route('admin.jobs'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.jobs.form')
            ->layout('layouts.admin', ['title' => $this->jobId ? 'Edit Job' : 'New Job']);
    }
}
