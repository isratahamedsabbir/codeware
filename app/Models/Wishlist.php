<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved favorite product for an authenticated user — see
 * App\Support\Favorites (guests keep favorites in session, and those are
 * merged into rows here the moment the user signs in).
 */
class Wishlist extends Model
{
    protected $fillable = ['user_id', 'product_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
