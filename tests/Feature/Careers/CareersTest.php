<?php

use App\Livewire\Admin\Careers\Applications;
use App\Livewire\Admin\Careers\Index;
use App\Livewire\Frontend\JobApplicationForm;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');
    Storage::fake('local');
});

it('lists only open, unexpired job posts', function () {
    JobPost::create(['title' => 'Open Role', 'status' => 'open']);
    JobPost::create(['title' => 'Draft Role', 'status' => 'draft']);
    JobPost::create(['title' => 'Expired Role', 'status' => 'open', 'deadline' => now()->subDay()]);

    $this->get(route('careers'))
        ->assertOk()
        ->assertSee('Open Role')
        ->assertDontSee('Draft Role')
        ->assertDontSee('Expired Role');
});

it('links to careers from the ecommerce footer', function () {
    $this->get(route('careers'))->assertSee(route('careers'), false);
});

it('hides draft posts and shows the form on an open one', function () {
    $draft = JobPost::create(['title' => 'Hidden', 'status' => 'draft']);
    $open = JobPost::create(['title' => 'Visible', 'status' => 'open']);

    $this->get(route('careers.show', $draft->slug))->assertNotFound();
    $this->get(route('careers.show', $open->slug))->assertOk()->assertSee('Submit application');
});

it('stores an application with a private résumé', function () {
    $job = JobPost::create(['title' => 'Dev', 'status' => 'open']);

    Livewire::test(JobApplicationForm::class, ['jobPostId' => $job->id])
        ->set('name', 'Jane')->set('email', 'jane@example.com')->set('phone', '0123')
        ->set('resume', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
        ->call('apply')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $application = JobApplication::firstOrFail();
    expect($application->status)->toBe('pending')->and($application->resume_original_name)->toBe('cv.pdf');
    Storage::disk('local')->assertExists($application->resume_path);
});

it('rejects applications to a closed post', function () {
    $job = JobPost::create(['title' => 'Dev', 'status' => 'closed']);

    Livewire::test(JobApplicationForm::class, ['jobPostId' => $job->id])
        ->set('name', 'Jane')->set('email', 'jane@example.com')->set('phone', '0123')
        ->set('resume', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
        ->call('apply')
        ->assertHasErrors('resume');

    expect(JobApplication::count())->toBe(0);
});

it('renders the admin job posts and applications screens and manages them', function () {
    Role::findOrCreate('admin', 'web');
    $this->actingAs(User::factory()->admin()->create());

    $job = JobPost::create(['title' => 'Admin Listed Role', 'status' => 'open']);
    $application = JobApplication::create([
        'job_post_id' => $job->id, 'name' => 'Jane', 'email' => 'j@example.com', 'phone' => '1',
        'resume_path' => 'resumes/1/cv.pdf', 'resume_original_name' => 'cv.pdf',
    ]);
    Storage::disk('local')->put('resumes/1/cv.pdf', 'pdf');

    $this->get(route('admin.careers'))->assertOk()->assertSee('Admin Listed Role');
    $this->get(route('admin.career-applications'))->assertOk()->assertSee('Jane');
    $this->get(route('admin.career-applications.resume', $application->id))->assertOk()->assertDownload('cv.pdf');

    Livewire::test(Index::class)
        ->set('title', 'New Role')->set('status', 'open')->call('save')->assertHasNoErrors();
    expect(JobPost::where('title', 'New Role')->value('slug'))->toBe('new-role');

    Livewire::test(Applications::class)
        ->call('setStatus', $application->id, 'shortlisted');
    expect($application->fresh()->status)->toBe('shortlisted');
});
