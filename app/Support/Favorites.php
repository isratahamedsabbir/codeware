<?php

namespace App\Support;

use App\Models\Product;
use App\Models\User;
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
     * Memoized ids() for the current request, so the per-product
     * WishlistButton on a storefront grid resolves them from one query.
     *
     * Without it every button's mount() re-reads the same rows: a signed-in
     * shopper's 24-card shop page ran the identical
     * `select product_id from wishlists where user_id = ?` 24 times. Keyed by
     * user id because the guest half of the answer lives in the session and
     * does not change identity mid-request, while the signed-in half is
     * per-user. Cleared by forgetIds() on every write (toggle, merge) so a
     * toggle immediately re-renders the header count and the buttons.
     *
     * @var array<int|string, array<int, int>>
     */
    private static array $ids = [];

    /**
     * Every favorited product id, session + database combined.
     *
     * @return array<int, int>
     */
    public static function ids(): array
    {
        $user = Auth::user();
        $key = $user?->id ?? 'guest';

        return self::$ids[$key] ??= self::resolveIds($user);
    }

    /**
     * Drops the memoized ids so the next read re-queries. Called by every
     * method that changes the wishlist, and by the Login listener, since
     * signing in changes the user the answer is keyed by.
     */
    public static function forgetIds(): void
    {
        self::$ids = [];
    }

    /**
     * @return array<int, int>
     */
    private static function resolveIds(?User $user): array
    {
        $ids = session('wishlist', []);

        if ($user) {
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
                self::forgetIds();

                return false;
            }

            Wishlist::create(['user_id' => $user->id, 'product_id' => $productId]);
            self::forgetIds();

            return true;
        }

        $ids = self::ids();

        if (in_array($productId, $ids, true)) {
            session(['wishlist' => array_values(array_diff($ids, [$productId]))]);
            self::forgetIds();

            return false;
        }

        $ids[] = $productId;
        session(['wishlist' => array_values(array_unique($ids))]);
        self::forgetIds();

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
        self::forgetIds();
    }
}
