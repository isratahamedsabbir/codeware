<?php

namespace App\Models;

use App\Notifications\AdminAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::created(function (Contact $contact) {
            Notification::send(
                User::where('is_admin', true)->get(),
                new AdminAlert(
                    'New contact message',
                    "{$contact->full_name}: {$contact->subject}",
                    route('admin.contacts'),
                ),
            );
        });
    }

    protected $fillable = [
        'full_name',
        'phone_number',
        'email',
        'subject',
        'message',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'unread');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('status', 'read');
    }
}
