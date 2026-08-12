<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'link',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Create a notification for every admin user.
     */
    public static function notifyAdmins(string $title, ?string $message = null, ?string $link = null): void
    {
        $adminIds = User::where('is_admin', true)->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $now = now();

        static::insert($adminIds->map(fn ($id) => [
            'user_id' => $id,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }
}
