<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\JobPost;

/**
 * The Careers pages — the open job posts and a single post with its application
 * form. What they look like is the active theme's careers.blade.php / career.blade.php.
 */
trait RendersCareers
{
    public function careers()
    {
        $open = JobPost::open()->orderBy('sort_order')->latest()->get();
        $department = (string) request()->query('department', '');

        return $this->view('careers', [
            'jobs' => $department === '' ? $open : $open->where('department', $department)->values(),
            'departments' => $open->pluck('department')->filter()->unique()->sort()->values(),
            'department' => $department,
            'totalOpen' => $open->count(),
            'title' => __('Careers'),
            'currentSlug' => 'careers',
        ]);
    }

    public function career(string $slug)
    {
        // A closed or past-deadline post still resolves (a shared link shouldn't
        // 404 the day it closes) but the template swaps the form for a notice.
        $job = JobPost::where('status', '!=', 'draft')->where('slug', $slug)->firstOrFail();

        return $this->view('career', [
            'job' => $job,
            'title' => $job->title,
            'currentSlug' => 'careers',
        ]);
    }
}
