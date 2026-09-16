<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records which user created the row, once, at creation time — never
 * touched again on later updates, unlike a "last edited by" field (see
 * Page::$user_id, overwritten on every save by whoever's saving it). Setting
 * it here in a `creating` hook, rather than in every Form component that
 * creates one of these, means every creation path (Livewire forms, seeders,
 * tinker, factories) gets it automatically and consistently.
 */
trait HasCreator
{
    protected static function bootHasCreator(): void
    {
        static::creating(function ($model) {
            if ($model->created_by) {
                return;
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();

                return;
            }

            // Seeders, tinker, and other console-created rows run with no
            // authenticated user, which used to leave created_by null forever
            // — showing "—" in every "Created by" column. Fall back to the
            // Super Admin so seeded data has a real owner instead.
            $model->created_by = User::where('is_admin', true)->value('id');
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
