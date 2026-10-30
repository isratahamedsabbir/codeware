<?php

namespace App\Http\Controllers\Auth;

use App\Mail\MfaOtpMail;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Mfa;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers the two-factor challenge with a code mailed to the account's address,
 * for the accounts that switched that on.
 *
 * Shaped exactly like TwoFactorPasskeyController, and for the same reasons: the
 * challenged account comes from the session's `login.id` and never from the
 * request, the routes are domainless so each portal's host-scoped session is the
 * one read, and the completion half is Fortify's own (same events, guard login,
 * session regeneration and response), so a sign-in finished here is
 * indistinguishable from one finished with an authenticator code.
 *
 * The code itself is App\Services\OtpService's: hashed at rest, capped attempts,
 * resend cooldown and an hourly quota per address.
 */
class TwoFactorEmailOtpController extends Controller
{
    public function __construct(protected StatefulGuard $guard, protected OtpService $otp) {}

    /** Mail a fresh code to the challenged account. */
    public function send(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        $outcome = $this->otp->issue(
            $user->email,
            Mfa::OTP_PURPOSE_LOGIN,
            fn (string $code) => Mail::to($user->email)->send(new MfaOtpMail($code, $user->name)),
        );

        return back()->with(match ($outcome) {
            OtpService::SENT => ['email_otp_status' => __('We emailed a 6-digit code to :email.', ['email' => $this->mask($user->email)])],
            OtpService::THROTTLED => ['email_otp_status' => __('A code was just sent. Wait a minute before asking for another.')],
            OtpService::RATE_LIMITED => ['email_otp_error' => __('Too many codes requested. Try again later.')],
            default => ['email_otp_error' => __('We could not send the email. Try again, or use another method.')],
        });
    }

    /** Check the typed code, then finish the sign-in. */
    public function store(Request $request): Response|RedirectResponse
    {
        $user = $this->challengedUser($request);

        $request->validate([
            'email_code' => ['required', 'digits:6'],
        ]);

        $outcome = $this->otp->verify($user->email, Mfa::OTP_PURPOSE_LOGIN, (string) $request->input('email_code'));

        if ($outcome !== OtpService::VERIFIED) {
            event(new TwoFactorAuthenticationFailed($user));

            throw ValidationException::withMessages([
                'email_code' => match ($outcome) {
                    OtpService::LOCKED => __('Too many wrong codes. Request a new one.'),
                    OtpService::EXPIRED => __('That code has expired. Request a new one.'),
                    default => __('That code was not accepted. Check it and try again.'),
                },
            ]);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        // See TwoFactorPasskeyController::store(): the half-finished login has to
        // go, or /two-factor-challenge stays reachable on an authenticated session.
        $request->session()->forget('login.id');

        $this->guard->login($user, (bool) $request->session()->get('login.remember', false));

        $request->session()->regenerate();

        return app(TwoFactorLoginResponse::class)->toResponse($request);
    }

    /**
     * The half-authenticated account, or a bounce back to the login that started
     * it. Also refuses an account that has not switched this factor on, so the
     * route cannot be a way past a TOTP- or passkey-only challenge.
     */
    private function challengedUser(Request $request): User
    {
        $id = $request->session()->get('login.id');

        $user = $id ? User::find($id) : null;

        if (! $user) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        if ($user->is_blocked || $user->hasInactiveRole()) {
            $request->session()->forget(['login.id', 'login.remember']);

            throw new HttpResponseException(redirect()->route('login'));
        }

        if (! Mfa::emailOtpEnabledFor($user)) {
            throw new HttpResponseException(redirect()->route('two-factor.login'));
        }

        return $user;
    }

    /** j***@example.com — enough to recognise the mailbox, not to harvest it. */
    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
