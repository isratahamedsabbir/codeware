<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FileManagerPath;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileManagerController extends Controller
{
    /**
     * Stream a file's raw bytes (images/video previews, or a forced download
     * when `?download=1` is passed). Uses Symfony's BinaryFileResponse so
     * Range requests work, which video playback needs for seeking.
     */
    public function raw(Request $request): BinaryFileResponse
    {
        $absolute = FileManagerPath::resolve((string) $request->query('path', ''));

        if (! is_file($absolute)) {
            throw new NotFoundHttpException('Not a file.');
        }

        return $request->boolean('download')
            ? response()->download($absolute, basename($absolute))
            : response()->file($absolute);
    }
}
