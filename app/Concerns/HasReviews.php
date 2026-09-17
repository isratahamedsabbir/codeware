<?php

namespace App\Concerns;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared by every reviewable model (Post, Product, Service) — all backed by
 * the single polymorphic `reviews` table (reviewable_type/reviewable_id),
 * mirroring HasComments rather than a per-model reviews table.
 */
trait HasReviews
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Approved reviews only — what the public API actually shows.
     */
    public function approvedReviews(): MorphMany
    {
        return $this->reviews()->approved()->latest();
    }

    /**
     * Average approved rating (1 decimal), or null when there are no approved
     * reviews yet — the number a storefront typically badge next to a product.
     */
    public function averageRating(): ?float
    {
        $average = $this->reviews()->approved()->average('rating');

        return $average === null ? null : round((float) $average, 1);
    }
}
