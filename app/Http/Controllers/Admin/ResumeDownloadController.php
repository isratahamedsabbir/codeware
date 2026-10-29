<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Streams an applicant's résumé from the private disk — resumes are never publicly linked. */
class ResumeDownloadController extends Controller
{
    public function __invoke(int $id): StreamedResponse
    {
        $application = JobApplication::findOrFail($id);
        abort_unless(Storage::disk('local')->exists($application->resume_path), 404);

        return Storage::disk('local')->download($application->resume_path, $application->resume_original_name);
    }
}
