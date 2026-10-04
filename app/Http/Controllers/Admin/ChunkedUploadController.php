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

    /**
     * The only extensions this endpoint will ever store, decided here and not
     * by the caller.
     *
     * This list used to be `$request->allowedExt` with this string as the
     * fallback, which meant the allow-list was a request parameter: posting
     * `filename=shell.php&allowedExt=php` wrote a .php file to the public disk.
     * A stored extension that PHP-FPM will execute is remote code execution, so
     * the extension is now picked from this list and `allowedExt` can only ever
     * *narrow* it (the picker modal offers images-only, hence the parameter at
     * all). No executable extension appears below, and DANGEROUS_EXTENSIONS
     * asserts that stays true.
     */
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif',
        'pdf', 'mp4', 'mp3', 'doc', 'docx', 'xls', 'xlsx',
    ];

    /**
     * Extensions that must never reach the public disk, whatever else changes.
     *
     * Checked separately from ALLOWED_EXTENSIONS so that adding a 'doc' or a
     * new media type later cannot quietly add an executable one, and so the
     * intent survives a well-meaning edit to the list above.
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'exe', 'com', 'bat', 'cmd',
        'jsp', 'asp', 'aspx', 'jar', 'htaccess', 'htpasswd', 'ini', 'svg',
    ];

    /**
     * Ceiling on one chunk, and on every assembled file.
     *
     * The chunk cap is what actually bounds disk use: the total used to be
     * checked only after every byte had already been streamed to the public
     * disk, so declaring a huge totalChunks filled the disk and the check
     * afterwards deleted one file and gave up. Chunks are ~5MB from
     * resources/js/chunk-upload.js; 10MB leaves room for a bigger client chunk.
     */
    private const MAX_CHUNK_SIZE_BYTES = 10_485_760; // 10 MB

    private const MAX_TOTAL_SIZE_BYTES = 524_288_000; // 500 MB

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MediaLibrary::class);

        $validated = $request->validate([
            'chunk' => ['required', 'file', 'max:'.intdiv(self::MAX_CHUNK_SIZE_BYTES, 1024)],
            'chunkIndex' => ['required', 'integer', 'min:0'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'uploadId' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9\-]+$/'],
            'filename' => ['required', 'string', 'max:255'],
            'allowedExt' => ['nullable', 'string', 'max:255'],
        ]);

        $ext = strtolower(pathinfo($validated['filename'], PATHINFO_EXTENSION));

        if (in_array($ext, self::DANGEROUS_EXTENSIONS, true) || ! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['filename' => 'This file type is not allowed.']);
        }

        // The caller's list is a narrowing hint, never a widening one: a value
        // that is not already allowed above stays not-allowed here.
        if (($requested = $this->requestedExtensions($validated['allowedExt'] ?? null)) !== null
            && ! in_array($ext, $requested, true)) {
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

    /**
     * The caller's narrower allow-list, already intersected with the server's
     * own. Null when the caller did not narrow at all.
     *
     * @return array<int, string>|null
     */
    private function requestedExtensions(?string $allowedExt): ?array
    {
        if ($allowedExt === null || trim($allowedExt) === '') {
            return null;
        }

        return array_values(array_intersect(
            self::ALLOWED_EXTENSIONS,
            collect(explode(',', $allowedExt))
                ->map(fn ($ext) => strtolower(trim($ext)))
                ->filter()
                ->all()
        ));
    }

    private function assemble(string $chunkDir, int $totalChunks, string $ext, string $originalFilename): JsonResponse
    {
        Storage::disk('public')->makeDirectory('media');

        $finalRelativePath = 'media/'.Str::random(40).'.'.$ext;
        $finalAbsolutePath = Storage::disk('public')->path($finalRelativePath);

        $out = fopen($finalAbsolutePath, 'wb');
        $written = 0;

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = Storage::disk('local')->path($chunkDir.'/'.$i);

            if (! is_file($chunkPath)) {
                fclose($out);
                @unlink($finalAbsolutePath);
                Storage::disk('local')->deleteDirectory($chunkDir);

                throw ValidationException::withMessages(['filename' => "Upload failed — chunk {$i} is missing. Please retry."]);
            }

            $in = fopen($chunkPath, 'rb');
            $written += stream_copy_to_stream($in, $out);
            fclose($in);

            // Bailed out mid-write rather than after it: the file on the public
            // disk is deleted immediately, so an oversized upload never leaves
            // the bytes behind.
            if ($written > self::MAX_TOTAL_SIZE_BYTES) {
                fclose($out);
                @unlink($finalAbsolutePath);
                Storage::disk('local')->deleteDirectory($chunkDir);

                throw ValidationException::withMessages(['filename' => 'File is too large (max 500 MB).']);
            }
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
