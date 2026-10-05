<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * The API's second-factor challenge, which is the one place here that has no
 * session to park a half-finished login in.
 *
 * Every other door in this app — the three portals and Fortify's own — leans on
 * `login.id` in a host-scoped session to remember whose credential the next
 * request is supposed to satisfy. The customer API is stateless: the Next.js
 * frontend sits on its own origin and holds nothing but a Sanctum token, and
 * the API has no cookie session to write to even if it wanted one. So the
 * "which account is being challenged, and with what" state is carried in an
 * encrypted token handed back to the client and presented on the answering
 * request instead.
 *
 * Why encryption rather than an opaque database row or a signed value:
 *
 *   - It is authenticated. A client cannot mint a challenge naming an account it
 *     has not proved a password for, nor edit the user id inside one, which is
 *     the whole attack this shape has to not have.
 *   - It is self-contained. The WebAuthn options have to survive the round trip
 *     out to the authenticator and back, and there is nowhere else to keep them.
 *     Carrying them inside the token is what lets a passkey assertion be checked
 *     against the challenge that was actually issued.
 *   - It is a `decrypt()` away from being correct with no extra infrastructure,
 *     and this app already encrypts Fortify's own TOTP secrets with the same key.
 *
 * The trade-off it does NOT solve is replay: an encrypted token stays valid until
 * it expires, so it is minted with a short TTL and the endpoints that answer it
 * are throttled. TOTP codes have Fortify's provider behind them (which itself
 * refuses reuse inside its window) and passkey assertions are refused a second
 * time by the stored signature counter.
 */
final class ApiMfa
{
    /**
     * How long a minted challenge stays answerable. Short because it is a
     * bearer token for "the password half was already accepted": long enough to
     * type a six-digit code, far too short to be worth writing down.
     */
    public const TTL_MINUTES = 5;

    /** TOTP is available as an answer to the challenge. */
    public const METHOD_TOTP = 'totp';

    /** A WebAuthn passkey is available as an answer to the challenge. */
    public const METHOD_PASSKEY = 'passkey';

    /**
     * A printed recovery code is available.
     *
     * Only ever alongside TOTP: Fortify generates recovery codes as part of
     * confirming a TOTP secret, so a passkey-only account has none, and offering
     * the option would be offering something that cannot succeed.
     */
    public const METHOD_RECOVERY_CODE = 'recovery_code';

    /**
     * Whether this login has to be challenged before a token is issued.
     *
     * Policy *and* enrolment, deliberately — see the reasoning on
     * Mfa::isRequiredFor(). An account with no factor is not challenged, because
     * there is nothing to answer with and no ceremony on this surface to enrol
     * one: the only ways to get a second factor are the web profile screen and
     * the store, both of which need a browser the API caller does not have. Such
     * an account is in exactly the state it was in before this existed, and
     * holding its token back would lock a customer out of the storefront with no
     * way back in.
     */
    public static function isRequiredFor(User $user): bool
    {
        return Mfa::isRequiredFor($user) && Mfa::hasFactorFor($user);
    }

    /**
     * The answers this account can give, as stable strings for the client to
     * branch on. Empty is not a possibility here — the caller has already
     * checked isRequiredFor(), which requires a factor.
     *
     * @return list<string>
     */
    public static function methodsFor(User $user): array
    {
        $methods = [];

        if (Mfa::totpEnabledFor($user)) {
            $methods[] = self::METHOD_TOTP;
            $methods[] = self::METHOD_RECOVERY_CODE;
        }

        if (Mfa::hasPasskeyFor($user)) {
            $methods[] = self::METHOD_PASSKEY;
        }

        return $methods;
    }

    /**
     * Mint a challenge for an account whose password has just been accepted.
     *
     * Passkey options are generated here rather than on demand so that answering
     * is a single round trip for the common case; options() exists for the case
     * where the user takes longer than the WebAuthn timeout to pick up their
     * phone, and re-mints through issueWithPasskeyOptions() below.
     */
    public static function issue(User $user): string
    {
        return self::issueWithPasskeyOptions(
            $user,
            Mfa::hasPasskeyFor($user)
                ? WebAuthn::toJson(app(GenerateVerificationOptions::class)($user))
                : null
        );
    }

    /**
     * The one place a challenge payload is built, so the shape and the expiry
     * cannot drift between a fresh login and a refreshed ceremony.
     *
     * @param  ?string  $passkeyOptions  Serialized PublicKeyCredentialRequestOptions, or null for a TOTP-only account.
     */
    public static function issueWithPasskeyOptions(User $user, ?string $passkeyOptions): string
    {
        return encrypt([
            'user_id' => $user->getKey(),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
            'passkey_options' => $passkeyOptions,
        ]);
    }

    /**
     * The account a challenge token names, or null if it cannot be honoured.
     *
     * Null covers every way the token can be stale: tampered with (decryption
     * fails), expired, or naming an account that no longer exists — and, checked
     * again here rather than trusted from mint time, an account that has been
     * blocked or had its role deactivated since the password was accepted. A
     * second factor must never be a way back into an account that is no longer
     * allowed in.
     */
    public static function resolve(string $token): ?User
    {
        try {
            $payload = decrypt($token);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        $user = User::find($payload['user_id'] ?? null);

        if (! $user instanceof User || $user->is_blocked || $user->hasInactiveRole()) {
            return null;
        }

        // The account may also have lost its factor since the challenge was
        // minted (a revoked passkey, TOTP disabled from another device). There is
        // then nothing that could legitimately answer this token.
        return Mfa::hasFactorFor($user) ? $user : null;
    }

    /**
     * The WebAuthn options this challenge was minted with, or null when it has
     * none (a TOTP-only account) or cannot be read.
     */
    public static function passkeyOptions(string $token): ?string
    {
        try {
            $payload = decrypt($token);
        } catch (DecryptException) {
            return null;
        }

        $options = is_array($payload) ? ($payload['passkey_options'] ?? null) : null;

        return is_string($options) && $options !== '' ? $options : null;
    }

    /**
     * The body an auth endpoint returns instead of a token when the login has to
     * be challenged.
     *
     * No `token` key at all rather than a null one: a client that only checks for
     * `data.token` then gets undefined and falls into its unauthenticated path,
     * which is the correct outcome, whereas `"token": null` invites a client to
     * store it and mistake itself for signed in.
     *
     * @return array<string, mixed>
     */
    public static function challengePayload(User $user, string $token): array
    {
        return [
            'mfa_required' => true,
            'mfa_token' => $token,
            'methods' => self::methodsFor($user),
            'expires_in' => self::TTL_MINUTES * 60,
            'user' => ApiUser::payload($user),
        ];
    }

    /**
     * Deserialize a challenge's stored options for the verifier. Null when the
     * token is unreadable, which the caller treats as "not answerable with a
     * passkey" rather than as an error in its own right.
     */
    public static function toRequestOptions(?string $serialized): ?PublicKeyCredentialRequestOptions
    {
        if ($serialized === null) {
            return null;
        }

        try {
            return WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
