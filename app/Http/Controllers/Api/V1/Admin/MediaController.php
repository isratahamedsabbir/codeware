<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 30), 100);

        $media = MediaLibrary::orderByDesc('created_at')
            ->when($request->query('type'), fn ($q, $type) => $q->where('mime_type', 'like', "{$type}/%"))
            ->paginate($perPage);

        return response()->json([
            'data' => $media->map(fn ($item) => [
                'id' => $item->id,
                'filename' => $item->filename,
                'url' => $item->url,
                'mime_type' => $item->mime_type,
                'file_size' => $item->file_size,
                'alt_text' => $item->alt_text,
                'created_at' => $item->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:jpg,jpeg,png,webp,gif,pdf,mp4',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $path = $file->store('media', 'public');

        $mime = $file->getMimeType();
        $fileType = explode('/', $mime)[0];

        $media = MediaLibrary::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'filename' => $file->hashName(),
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'url' => Storage::url($path),
            'mime_type' => $mime,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
        ]);

        return response()->json([
            'data' => [
                'id' => $media->id,
                'filename' => $media->original_filename,
                'url' => $media->url,
                'mime_type' => $media->mime_type,
                'file_size' => $media->file_size,
                'alt_text' => $media->alt_text,
                'created_at' => $media->created_at->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $media = MediaLibrary::findOrFail($id);
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return response()->json(null, 204);
    }
}
