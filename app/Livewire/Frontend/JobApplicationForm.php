<?php

namespace App\Livewire\Frontend;

use App\Models\JobApplication;
use App\Models\JobPost;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The public "Apply now" form on a job post. The résumé goes to the private
 * `local` disk (never web-reachable); admins download it through the
 * authenticated admin.career-applications.resume route.
 */
class JobApplicationForm extends Component
{
    use WithFileUploads;

    public int $jobPostId;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $cover_letter = '';

    public $resume = null;

    public bool $sent = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'cover_letter' => 'nullable|string|max:5000',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ];
    }

    public function apply(): void
    {
        $job = JobPost::findOrFail($this->jobPostId);

        // Re-checked on submit: the page may have been open past the deadline.
        if (! $job->isAcceptingApplications()) {
            $this->addError('resume', __('This position is no longer accepting applications.'));

            return;
        }

        $data = $this->validate();

        $original = $this->resume->getClientOriginalName();
        $filename = now()->format('YmdHis').'_'.Str::random(6).'.'.$this->resume->getClientOriginalExtension();
        $path = $this->resume->storeAs("resumes/{$job->id}", $filename, 'local');

        JobApplication::create([
            'job_post_id' => $job->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'cover_letter' => filled($data['cover_letter']) ? $data['cover_letter'] : null,
            'resume_path' => $path,
            'resume_original_name' => Str::limit($original, 200, ''),
        ]);

        $this->reset(['name', 'email', 'phone', 'cover_letter', 'resume']);
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.frontend.job-application-form');
    }
}
