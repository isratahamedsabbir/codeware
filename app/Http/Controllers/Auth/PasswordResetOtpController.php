<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Providers\FortifyServiceProvider;
use App\Services\OtpService;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Forgot-password by emailed code, in place of Fortify's token link.
 *
 * Fortify's own reset routes are switched off (config/fortify.php) and these
 * take their names, so everything that points at a password reset — the themed
 * pages, the storefront's own links — still resolves. The three-step flow is:
 * ask for a code, redeem it, set a new password.
 *
 * The redeemed code is what authorizes the last step. It is spent by verify(),
 * and what it leaves behind is a session flag naming the address it was redeemed
 * for; the password form reads that address from the session and takes nothing
 * from the request, so there is no window between the two steps in which the
 * address could be swapped for somebody else's.
 */
class PasswordResetOtpController extends Controller
{
    // The new password is held to exactly the rules every other password here is
    // held to, rather than a second set invented for this form.
    use PasswordValidationRules;

    /**
     * The address whose code has been redeemed in this session, awaiting a new
     * password. Held for the length of the session rather than flashed, since
     * the password form is a separate request from the verification one.
     */
    public const VERIFIED_SESSION_KEY = 'password-reset.verified';

    /**
     * The address a code was just sent to, so the verification screen can show
     * which one it is asking about without the form having to carry it.
     */
    public const PENDING_SESSION_KEY = 'password-reset.pending';

    public function __construct(private readonly PasswordResetService $resets) {}

    /** The form that asks which address to send a code to. */
    public function create(): mixed
    {
        return FortifyServiceProvider::themedView(
            'frontend.themes.ecommerce.auth.forgot-password',
            'pages::auth.forgot-password',
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = $this->resets->requestCode($validated['email']);

        if ($status === OtpService::FAILED) {
            return back()->withErrors([
                'email' => __('We could not send the code right now. Please try again in a moment.'),
            ])->withInput();
        }

        // Enough codes have gone to this address in the last hour that sending
        // another would be the problem rather than the fix. Said plainly, because
        // it reaches the customer exactly as it reaches an attacker using their
        // address: nobody is told anything about whether the account exists, and
        // nobody is left staring at a code that will not arrive.
        if ($status === OtpService::RATE_LIMITED) {
            return back()->withErrors([
                'email' => __('You have asked for several codes already. Please wait before asking for another.'),
            ])->withInput();
        }

        // A THROTTLED answer means a code is already on its way or already
        // waiting — a cooldown only exists alongside a live code, and both are
        // dropped together the moment one is spent (OtpService::verify). That is
        // exactly the position a customer is in who ordered a moment ago as a
        // guest: the claim code from that order is in their inbox, and telling
        // them to wait a minute before they may use it would be pure friction.
        // So they are sent on to type it. An address with no account reports
        // SENT without anything being mailed (see PasswordResetService), and is
        // carried down the same road, so the message never depends on whether
        // the account exists.
        $request->session()->put(self::PENDING_SESSION_KEY, trim($validated['email']));

        return redirect()->route('password.verify');
    }

    /** The form the emailed code is typed into. */
    public function verifyForm(Request $request): mixed
    {
        $email = (string) $request->session()->get(self::PENDING_SESSION_KEY);

        // Without a pending address there is no code outstanding to type, and
        // the screen could not say which inbox it is asking about anyway.
        if ($email === '') {
            return redirect()->route('password.request');
        }

        return FortifyServiceProvider::themedView(
            'frontend.themes.ecommerce.auth.verify-code',
            'pages::auth.verify-code',
        )->with('email', $email);
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = (string) $request->session()->get(self::PENDING_SESSION_KEY);

        if ($email === '') {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $status = $this->resets->verifyCode($email, $validated['code']);

        if ($status === OtpService::VERIFIED) {
            $request->session()->put(self::VERIFIED_SESSION_KEY, $email);
            $request->session()->forget(self::PENDING_SESSION_KEY);

            return redirect()->route('password.reset');
        }

        $message = match ($status) {
            OtpService::LOCKED => __('Too many wrong attempts. Request a new code to continue.'),
            OtpService::EXPIRED => __('That code has expired. Request a new one to continue.'),
            default => __('That code is incorrect. Please try again.'),
        };

        // LOCKED and EXPIRED both mean there is nothing left to type, so the
        // pending address goes with them and the customer is sent back for a
        // fresh code rather than left staring at a form that cannot work.
        if ($status !== OtpService::INVALID) {
            $request->session()->forget(self::PENDING_SESSION_KEY);

            return redirect()->route('password.request')->withErrors(['code' => $message]);
        }

        return back()->withErrors(['code' => $message]);
    }

    /** The form that sets the new password. */
    public function resetForm(Request $request): mixed
    {
        if (! $request->session()->has(self::VERIFIED_SESSION_KEY)) {
            return redirect()->route('password.request');
        }

        return FortifyServiceProvider::themedView(
            'frontend.themes.ecommerce.auth.reset-password',
            'pages::auth.reset-password',
        )->with('email', $request->session()->get(self::VERIFIED_SESSION_KEY));
    }

    public function reset(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::VERIFIED_SESSION_KEY);

        if (! $email) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'password' => $this->passwordRules(),
            // Given its own rule as well as the "confirmed" one, so the confirmed
            // value itself comes back from validate() to be handed on.
            'password_confirmation' => ['required', 'string'],
        ]);

        $this->resets->setPassword($email, $validated['password'], $validated['password_confirmation']);

        // The new password is of no use to anyone still holding the session that
        // set it, and the code that authorized it has been spent, so the session
        // ends here. These routes are guest-only, so this is the ordinary case
        // rather than an edge one — leaving the session alive would keep the
        // pre-reset session id valid against a session that just changed a
        // password. The customer signs in afresh.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('Your password has been reset. Please sign in with your new password.'));
    }
}
