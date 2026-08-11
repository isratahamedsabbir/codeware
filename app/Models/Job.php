<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Job extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $table = 'career_jobs';

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'department_id', 'title', 'slug', 'image', 'document_file', 'description',
        'position', 'vacancy', 'deadline', 'location',
        'status', 'sort_order',
    ];

    protected $casts = [
        'vacancy'    => 'integer',
        'sort_order' => 'integer',
        'deadline'   => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Job $job) {
            if (empty($job->slug)) {
                $title = is_array($job->title)
                    ? ($job->title['en'] ?? reset($job->title))
                    : $job->title;
                $job->slug = Str::slug($title);
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
