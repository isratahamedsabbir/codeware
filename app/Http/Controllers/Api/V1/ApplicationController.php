<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationSubmitted;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $job = Job::find($value);
                    if (! $job || $job->status !== 'active') {
                        $fail('The selected job is not accepting applications.');
                    }
                },
            ],
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|max:255',
            'phone'  => 'required|string|max:50',
            'resume' => 'required|file|mimes:pdf,docx|max:5120',
        ]);

        $file            = $request->file('resume');
        $originalName    = $file->getClientOriginalName();
        $safeName        = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $path            = $file->storeAs(
            'resumes/' . $validated['job_id'],
            time() . '_' . $safeName,
            'local'
        );

        $application = JobApplication::create([
            'job_id'               => $validated['job_id'],
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'phone'                => $validated['phone'],
            'resume_path'          => $path,
            'resume_original_name' => $originalName,
            'status'               => 'pending',
        ]);

        $application->load('job');
        Mail::to($application->email)->send(new ApplicationSubmitted($application));

        return response()->json(['message' => 'Application submitted successfully.'], 201);
    }
}
