<?php

use App\Models\Department;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// --- Departments ---

it('returns all departments with open jobs count', function () {
    $dept = Department::factory()->create(['name' => ['en' => 'Engineering', 'bn' => '']]);
    Job::factory()->active()->create(['department_id' => $dept->id]);
    Job::factory()->create(['department_id' => $dept->id]); // inactive

    $this->getJson('/api/v1/departments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $dept->slug)
        ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'jobs_count']]]);
});

// --- Jobs ---

it('returns only active jobs on public listing', function () {
    Job::factory()->active()->create(['title' => ['en' => 'Active Job', 'bn' => '']]);
    Job::factory()->create(['title' => ['en' => 'Inactive Job', 'bn' => ''], 'status' => 'inactive']);
    Job::factory()->inactive()->create(['title' => ['en' => 'Inactive 2', 'bn' => '']]);

    $this->getJson('/api/v1/jobs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active Job');
});

it('filters jobs by department slug', function () {
    $dept = Department::factory()->create();
    Job::factory()->active()->create(['department_id' => $dept->id]);
    Job::factory()->active()->create(); // different department

    $this->getJson("/api/v1/jobs?department={$dept->slug}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches jobs by title', function () {
    Job::factory()->active()->create(['title' => ['en' => 'Senior Engineer', 'bn' => '']]);
    Job::factory()->active()->create(['title' => ['en' => 'Marketing Manager', 'bn' => '']]);

    $this->getJson('/api/v1/jobs?search=Engineer')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns paginated jobs with meta', function () {
    Job::factory()->count(5)->active()->create();

    $this->getJson('/api/v1/jobs?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5);
});

it('returns job detail by slug with description', function () {
    $dept = Department::factory()->create();
    $job = Job::factory()->active()->create([
        'department_id' => $dept->id,
        'title'         => ['en' => 'Detail Job', 'bn' => ''],
        'description'   => ['en' => 'Full description here', 'bn' => ''],
    ]);

    $this->getJson("/api/v1/jobs/{$job->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $job->slug)
        ->assertJsonPath('data.description', 'Full description here')
        ->assertJsonStructure(['data' => [
            'id', 'slug', 'title', 'description', 'position',
            'vacancy', 'deadline', 'location', 'status', 'department',
        ]]);
});

it('returns 404 for inactive job on public endpoint', function () {
    $job = Job::factory()->create(['status' => 'inactive']);
    $this->getJson("/api/v1/jobs/{$job->slug}")->assertNotFound();
});

it('returns 404 for inactive job on public endpoint via factory', function () {
    $job = Job::factory()->inactive()->create();
    $this->getJson("/api/v1/jobs/{$job->slug}")->assertNotFound();
});

// --- Applications ---

it('applicant can submit an application with resume', function () {
    Storage::fake('local');
    Mail::fake();

    $job = Job::factory()->active()->create();
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->postJson('/api/v1/applications', [
        'job_id' => $job->id,
        'name'   => 'Jane Doe',
        'email'  => 'jane@example.com',
        'phone'  => '+8801700000000',
        'resume' => $file,
    ])->assertCreated()
      ->assertJsonPath('message', 'Application submitted successfully.');

    expect(JobApplication::where('email', 'jane@example.com')->exists())->toBeTrue();
    Mail::assertSent(\App\Mail\ApplicationSubmitted::class);
});

it('stores resume in private local disk', function () {
    Storage::fake('local');
    Mail::fake();

    $job = Job::factory()->active()->create();
    $file = UploadedFile::fake()->create('my_cv.pdf', 200, 'application/pdf');

    $this->postJson('/api/v1/applications', [
        'job_id' => $job->id,
        'name'   => 'John',
        'email'  => 'john@example.com',
        'phone'  => '+8801700000001',
        'resume' => $file,
    ])->assertCreated();

    $application = JobApplication::where('email', 'john@example.com')->firstOrFail();
    Storage::disk('local')->assertExists($application->resume_path);
});

it('rejects application for a non-active job', function () {
    $job = Job::factory()->inactive()->create();
    $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

    $this->postJson('/api/v1/applications', [
        'job_id' => $job->id,
        'name'   => 'Applicant',
        'email'  => 'a@example.com',
        'phone'  => '+8801700000002',
        'resume' => $file,
    ])->assertUnprocessable()
      ->assertJsonValidationErrors(['job_id']);
});

it('rejects application with invalid file type', function () {
    $job = Job::factory()->active()->create();
    $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

    $this->postJson('/api/v1/applications', [
        'job_id' => $job->id,
        'name'   => 'Applicant',
        'email'  => 'a@example.com',
        'phone'  => '+8801700000003',
        'resume' => $file,
    ])->assertUnprocessable()
      ->assertJsonValidationErrors(['resume']);
});

it('rejects application when required fields are missing', function () {
    $this->postJson('/api/v1/applications', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['job_id', 'name', 'email', 'phone', 'resume']);
});
