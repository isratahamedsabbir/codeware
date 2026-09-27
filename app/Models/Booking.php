<?php

namespace App\Models;

use App\Mail\TemplateDrivenMail;
use App\Notifications\AdminAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * A visitor's request to be contacted about a service.
 *
 * Not an order: nothing is reserved, priced or paid here. A booking is "I would
 * like this service, contact me", and the date is agreed afterwards — see the
 * note on the status column in the create migration for why no date is stored.
 *
 * Written by Frontend\BookService on the storefront and read by the admin
 * Bookings screen, the same shape as Contact ↔ the admin Contacts inbox.
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /** A request nobody has dealt with yet — what the admin list opens on. */
    public const STATUS_NEW = 'new';

    /** Dealt with. Kept rather than deleted, so there is a record it happened. */
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_COMPLETED];

    protected $fillable = [
        'service_id',
        'full_name',
        'email',
        'phone_number',
        'message',
        'status',
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    protected static function booted(): void
    {
        static::created(function (Booking $booking) {
            $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get();

            if ($admins->isEmpty()) {
                return;
            }

            $serviceName = $booking->service?->name;

            // The bell notification alone is easy to miss on a phone that is
            // face down, and a booking that nobody sees is a booking that never
            // gets answered. Mail is the channel that actually reaches someone,
            // so both are sent - and to the same admin accounts, so there is one
            // place to change who gets told.
            //
            // A notification or transport problem must not cost the visitor their
            // request. The row is already stored and the admin list still has it,
            // so neither send is allowed to throw out of the model's event: a
            // booking that throws here reports a failure to someone who then
            // submits again, and now there are two of their real requests in the
            // inbox. Report instead, and let the admin list be the record.
            try {
                Notification::send(
                    $admins,
                    new AdminAlert(
                        'New service booking',
                        "{$booking->full_name} requested ".($serviceName ?: 'a service'),
                        route('admin.bookings'),
                    ),
                );

                Mail::to($admins->pluck('email')->filter()->unique())
                    ->send(new TemplateDrivenMail(
                        "New service booking from {$booking->full_name}",
                        self::bodyFor($booking),
                    ));
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    /**
     * The email body. Plain HTML, with the one field that matters - the reply
     * address - as a real mailto, so replying needs no copying.
     */
    protected static function bodyFor(Booking $booking): string
    {
        $rows = [
            'Name' => e($booking->full_name),
            'Email' => '<a href="mailto:'.e($booking->email).'">'.e($booking->email).'</a>',
            'Service' => e($booking->service?->name ?: '—'),
        ];

        if (filled($booking->phone_number)) {
            $rows['Phone'] = e($booking->phone_number);
        }

        if (filled($booking->message)) {
            $rows['Message'] = nl2br(e($booking->message));
        }

        $lines = collect($rows)
            ->map(fn ($value, $label) => '<strong>'.$label.':</strong> '.$value)
            ->implode('<br>');

        return '<p>Someone asked to be contacted about a service.</p><p>'.$lines.'</p>';
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
