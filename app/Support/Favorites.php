<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Storefront favorites ("wishlist").
 *
 * Guests keep an array of product ids in the session; signed-in users get
 * real `wishlists` rows keyed by user id. When someone signs in, whatever they
 * favorited as a guest is merged into their rows (and the session bag dropped)
 * — see the Login listener wired up in AppServiceProvider.
 */
class Favorites
{
    /**
     * Every favorited product id, session + database combined.
     *
     * @return array<int, int>
     */
    public static function ids(): array
    {
        $ids = session('wishlist', []);

        if ($user = Auth::user()) {
            $ids = array_merge($ids, Wishlist::where('user_id', $user->id)->pluck('product_id')->all());
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public static function count(): int
    {
        return count(self::ids());
    }

    public static function isFavorited(int $productId): bool
    {
        return in_array($productId, self::ids(), true);
    }

    /**
     * Adds/removes a favorite; returns the new favourited state.
     */
    public static function toggle(int $productId): bool
    {
        $productId = (int) $productId;

        if ($user = Auth::user()) {
            $exists = Wishlist::where('user_id', $user->id)->where('product_id', $productId)->exists();

            if ($exists) {
                Wishlist::where('user_id', $user->id)->where('product_id', $productId)->delete();

                return false;
            }

            Wishlist::create(['user_id' => $user->id, 'product_id' => $productId]);

            return true;
        }

        $ids = self::ids();

        if (in_array($productId, $ids, true)) {
            session(['wishlist' => array_values(array_diff($ids, [$productId]))]);

            return false;
        }

        $ids[] = $productId;
        session(['wishlist' => array_values(array_unique($ids))]);

        return true;
    }

    /**
     * A query over the favorited products, scoped to what a signed-in user or
     * this guest's session has saved. Empty favorites yield a never-matching
     * query so callers can still `paginate()` uniformly.
     */
    public static function products(): Builder
    {
        $ids = self::ids();

        if (empty($ids)) {
            return Product::query()->whereRaw('1 = 0');
        }

        return Product::active()->whereIn('id', $ids);
    }

    /**
     * Persist the guest's session favorites into the signed-in user's rows.
     * Called on every Login event (see AppServiceProvider::boot()).
     */
    public static function mergeSessionIntoDatabase(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $sessionIds = session('wishlist', []);

        if (empty($sessionIds)) {
            return;
        }

        foreach ($sessionIds as $productId) {
            Wishlist::firstOrCreate(['user_id' => $user->id, 'product_id' => (int) $productId]);
        }

        session()->forget('wishlist');
    }
}
