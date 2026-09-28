<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Referral tracking — "who sent them here".
 *
 * Every account already carries a public USR-XXXXXXXX business code (see
 * App\Concerns\HasUniqueCode), so a signed-in customer's share links are tagged
 * with their own code and an order placed through one of those links remembers
 * who sent the buyer:
 *
 *     /products/some-product?ref=USR-K715J5QH
 *
 * The link is only a carrier. Nothing about it is trusted: the code is resolved
 * to a real account on arrival (App\Http\Middleware\CaptureReferral), and only
 * that account's id is kept, in the visitor's session, until an order is placed
 * (see App\Services\OrderPlacement). An order therefore records a referrer that
 * genuinely exists, and a shared URL is never a way to invent one.
 *
 * Last click wins: every ?ref= that resolves overwrites the one before it, so a
 * buyer who clicks two different people's links ends up attributed to the one
 * they acted on last. An unrecognised code is ignored rather than clearing the
 * session, so a mistyped or truncated link can't silently discard a real
 * attribution that is already in place.
 *
 * Self-referral is refused in both directions — a signed-in customer opening
 * their own link captures nothing (capture()), and a link whose referrer is the
 * buyer produces a null ref on the order (idFor()). The second check is the one
 * that matters, because the session outlives a login: someone can open a
 * friend's link as a guest, sign in afterwards, and the captured referrer is
 * compared against the account that actually places the order.
 */
final class Referral
{
    /**
     * The query-string key the ref rides on, both inbound (?ref= on any
     * storefront page) and outbound (the share row's links and clipboard).
     */
    public const QUERY_KEY = 'ref';

    /**
     * Session key holding the referrer's user id. An id rather than the code so
     * the order write is a plain FK assignment, and because a code is a guessable
     * public identifier that has no business sitting in a visitor's session bag.
     */
    private const SESSION_KEY = 'referral_id';

    /**
     * A well-formed User::code(), used to reject junk before it reaches the
     * database. The prefix and the column's own length are the two constraints
     * that matter here — the exact character count is HasUniqueCode's business
     * and must not be duplicated as a second thing to keep in step with it.
     */
    private const CODE_SHAPE = '/^USR-[A-Z0-9]{1,16}$/';

    /**
     * Records the referrer behind an inbound `?ref=USR-XXXXXXXX`, returning the
     * captured user id, or null when there was nothing to capture.
     *
     * A null return means "leave the session alone" rather than "no referrer" —
     * only a code that resolves to somebody else actually writes.
     */
    public static function capture(?string $code): ?int
    {
        $code = strtoupper(trim((string) $code));

        if (! preg_match(self::CODE_SHAPE, $code)) {
            return null;
        }

        $referrer = User::where('code', $code)->first();

        if (! $referrer) {
            return null;
        }

        if ($referrer->id === Auth::id()) {
            return null;
        }

        session([self::SESSION_KEY => $referrer->id]);

        return $referrer->id;
    }

    /**
     * The referrer this visitor is currently attributed to, or null when they
     * arrived without a link.
     */
    public static function pendingId(): ?int
    {
        $id = session(self::SESSION_KEY);

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * The referrer to stamp on an order being placed by $buyerId — null when the
     * session carries no link, or when the link is the buyer's own, because
     * nobody refers themselves.
     */
    public static function idFor(?int $buyerId): ?int
    {
        $referrerId = self::pendingId();

        return $referrerId !== null && $referrerId !== $buyerId ? $referrerId : null;
    }

    /**
     * Drops the captured referrer. Called once the order it belongs to is
     * committed, so a second order in the same session is not attributed to a
     * link that was already used.
     */
    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * The signed-in customer's own ref code, or null for a guest — there is
     * nobody to attribute an order to, which is why a guest's share links carry
     * no ref at all.
     */
    public static function code(): ?string
    {
        $code = Auth::user()?->code;

        return filled($code) ? (string) $code : null;
    }

    /**
     * The URL to hand to the social networks and the clipboard: the page's own
     * canonical, tagged with the signed-in customer's ref code so the order it
     * eventually produces is attributed to them. A guest's links are the bare
     * canonical.
     *
     * An admin-set canonical can carry a query string of its own, so the tag is
     * applied by rebuilding the query rather than by appending to it — otherwise
     * a canonical that already had a `ref` of its own would end up with two,
     * and which one won would depend on the order the server happened to read
     * them in.
     */
    public static function shareUrl(string $url): string
    {
        $code = self::code();

        if ($code === null) {
            return $url;
        }

        [$base, $query] = array_pad(explode('?', $url, 2), 2, '');

        parse_str($query, $params);

        $params[self::QUERY_KEY] = $code;

        return $base.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
