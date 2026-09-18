<?php

namespace App\Support;

use App\Models\MediaLibrary;
use Illuminate\Support\Facades\Storage;

/**
 * Turns an already-stored file on the "public" disk into a MediaLibrary row —
 * the shared tail end of every upload path (the plain Livewire upload in
 * Index.php/PickerModal.php, and the chunked upload assembled by
 * ChunkedUploadController), so watermarking/metadata/file-type detection
 * only lives in one place.
 */
class MediaLibraryUploader
{
    public static function store(string $diskPath, string $originalFilename, string $mimeType, int $fileSize, ?int $uploadedBy): MediaLibrary
    {
        ImageWatermarker::applyIfEnabled('public', $diskPath, $mimeType);

        $fileType = self::resolveFileType($mimeType);

        $metadata = [];

        if ($fileType === 'image') {
            $imageSize = @getimagesize(Storage::disk('public')->path($diskPath));
            if ($imageSize) {
                $metadata['width'] = $imageSize[0];
                $metadata['height'] = $imageSize[1];
            }
        }

        return MediaLibrary::create([
            'filename' => basename($diskPath),
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'disk' => 'public',
            'path' => $diskPath,
            'url' => Storage::disk('public')->url($diskPath),
            'uploaded_by' => $uploadedBy,
            'metadata' => $metadata,
        ]);
    }

    public static function resolveFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }
}
