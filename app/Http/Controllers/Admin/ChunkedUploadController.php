<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use App\Support\MediaLibraryUploader;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Receives one chunk of a large (>10MB) Media Library upload at a time —
 * the browser-side counterpart lives in resources/js/chunk-upload.js, wired
 * up from both the standalone Media Library page and the picker modal.
 * Files under the 10MB threshold never hit this and keep using Livewire's
 * own (single-request) upload via WithFileUploads.
 */
class ChunkedUploadController extends Controller
{
    use AuthorizesRequests;

    private const DEFAULT_ALLOWED_EXT = 'jpg,jpeg,png,gif,webp,avif,pdf,mp4,mp3,doc,docx,xls,xlsx';

    // Large-file path has no per-chunk size cap to speak of (chunks are
    // small by construction), but the assembled file still needs a ceiling
    // to stop someone chunking their way past disk space.
    private const MAX_TOTAL_SIZE_BYTES = 524_288_000; // 500 MB

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MediaLibrary::class);

        $validated = $request->validate([
            'chunk' => ['required', 'file'],
            'chunkIndex' => ['required', 'integer', 'min:0'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'uploadId' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9\-]+$/'],
            'filename' => ['required', 'string', 'max:255'],
            'allowedExt' => ['nullable', 'string', 'max:255'],
        ]);

        $allowedExt = collect(explode(',', $validated['allowedExt'] ?? self::DEFAULT_ALLOWED_EXT))
            ->map(fn ($ext) => strtolower(trim($ext)))
            ->filter()
            ->all();

        $ext = strtolower(pathinfo($validated['filename'], PATHINFO_EXTENSION));

        if (! in_array($ext, $allowedExt, true)) {
            throw ValidationException::withMessages(['filename' => 'This file type is not allowed.']);
        }

        $chunkDir = 'chunk-uploads/'.$validated['uploadId'];
        Storage::disk('local')->putFileAs($chunkDir, $validated['chunk'], (string) $validated['chunkIndex']);

        $receivedChunks = count(Storage::disk('local')->files($chunkDir));

        if ($receivedChunks < $validated['totalChunks']) {
            return response()->json(['done' => false, 'received' => $receivedChunks]);
        }

        return $this->assemble($chunkDir, $validated['totalChunks'], $ext, $validated['filename']);
    }

    private function assemble(string $chunkDir, int $totalChunks, string $ext, string $originalFilename): JsonResponse
    {
        Storage::disk('public')->makeDirectory('media');

        $finalRelativePath = 'media/'.Str::random(40).'.'.$ext;
        $finalAbsolutePath = Storage::disk('public')->path($finalRelativePath);

        $out = fopen($finalAbsolutePath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = Storage::disk('local')->path($chunkDir.'/'.$i);

            if (! is_file($chunkPath)) {
                fclose($out);
                @unlink($finalAbsolutePath);
                Storage::disk('local')->deleteDirectory($chunkDir);

                throw ValidationException::withMessages(['filename' => "Upload failed — chunk {$i} is missing. Please retry."]);
            }

            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);
        Storage::disk('local')->deleteDirectory($chunkDir);

        $fileSize = filesize($finalAbsolutePath);

        if ($fileSize > self::MAX_TOTAL_SIZE_BYTES) {
            @unlink($finalAbsolutePath);

            throw ValidationException::withMessages(['filename' => 'File is too large (max 500 MB).']);
        }

        $mimeType = mime_content_type($finalAbsolutePath) ?: 'application/octet-stream';

        $media = MediaLibraryUploader::store($finalRelativePath, $originalFilename, $mimeType, $fileSize, auth()->id());

        return response()->json([
            'done' => true,
            'media' => [
                'id' => $media->id,
                'url' => $media->url,
                'title' => $media->title ?? $media->original_filename,
                'alt' => $media->alt_text ?? '',
            ],
        ]);
    }
}
