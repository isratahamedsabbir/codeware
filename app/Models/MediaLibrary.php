<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibrary extends Model
{
    use HasFactory;

    protected $table = 'media_library';

    protected $fillable = [
        'uuid',
        'filename',
        'original_filename',
        'mime_type',
        'file_type',
        'file_size',
        'disk',
        'path',
        'url',
        'title',
        'alt_text',
        'caption',
        'description',
        'metadata',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'file_size' => 'integer',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    public function isDocument(): bool
    {
        return $this->file_type === 'document';
    }

    public function isVideo(): bool
    {
        return $this->file_type === 'video';
    }

    public function getDimensionsAttribute(): ?array
    {
        if ($this->isImage() && isset($this->metadata['width'], $this->metadata['height'])) {
            return [
                'width' => $this->metadata['width'],
                'height' => $this->metadata['height'],
            ];
        }

        return null;
    }

    public function getUrlAttribute(): ?string
    {
        $url = $this->attributes['url'] ?? null;

        if (!$url && $this->path) {
            $url = Storage::disk($this->disk)->url($this->path);
        }

        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = url($url);
        }

        return $url ?: null;
    }

    public function getFormattedSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }
}
