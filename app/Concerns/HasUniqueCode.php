<?php

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Auto-generates a human-friendly, unique business code for a model — a short
 * word prefix joined to random characters, e.g. Product → PRD-K9M4Q7X2. Used by
 * Product (code), User (code) and Order (order_number), replacing the older
 * per-model ad-hoc generators with one shared, collision-rolled implementation.
 *
 * Additive only: a value already present (pre-created row, backfill, explicit
 * assignment) is never overwritten; the code is only generated when empty.
 */
trait HasUniqueCode
{
    protected static function bootHasUniqueCode(): void
    {
        static::creating(function ($model) {
            $column = $model->uniqueCodeColumn();

            if (! empty($model->{$column})) {
                return;
            }

            $model->{$column} = static::generateUniqueCode($model->uniqueCodePrefix(), $column);
        });
    }

    /**
     * The column that stores the code — override to point at an existing
     * column (e.g. Order → 'order_number').
     */
    protected function uniqueCodeColumn(): string
    {
        return 'code';
    }

    /**
     * The word part of the code — override with a short, meaningful prefix
     * (e.g. Product 'PRD', User 'USR', Order 'ORD').
     */
    protected function uniqueCodePrefix(): string
    {
        return strtoupper(Str::substr(class_basename(static::class), 0, 3));
    }

    /**
     * 'PREFIX-' plus 8 uppercase alphanumerics. Uniqueness is re-checked (and
     * re-rolled on a clash) rather than trusted, so a recycled code — e.g. one
     * already taken by a soft-deleted row still sitting in the DB unique index
     * — can't fail the insert. withoutGlobalScopes() is deliberate: the check
     * must also see rows normal queries hide.
     */
    protected static function generateUniqueCode(string $prefix, string $column): string
    {
        do {
            $code = $prefix.'-'.strtoupper(Str::random(8));
        } while (static::withoutGlobalScopes()->where($column, $code)->exists());

        return $code;
    }
}
