<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResumeDownloadController extends Controller
{
    public function __invoke(Request $request, int $id): StreamedResponse
    {
        $application = JobApplication::findOrFail($id);

        abort_unless(Storage::disk('local')->exists($application->resume_path), 404);

        return Storage::disk('local')->download(
            $application->resume_path,
            $application->resume_original_name
        );
    }
}
