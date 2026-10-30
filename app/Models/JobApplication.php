<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    public const STATUSES = ['pending', 'reviewed', 'shortlisted', 'rejected'];

    protected $fillable = [
        'job_post_id', 'name', 'email', 'phone', 'cover_letter',
        'resume_path', 'resume_original_name', 'status',
    ];

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class)->withTrashed();
    }
}
