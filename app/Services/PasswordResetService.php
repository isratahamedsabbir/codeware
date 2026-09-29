<?php

namespace App\Services;

use App\Actions\Fortify\ResetUserPassword;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Setting a forgotten password by emailed code rather than by a link.
 *
 * The link this replaced pointed at the Next.js app (see
 * AppServiceProvider::configureCustomerAuthNotificationUrls) and was a dead end
 * until that page existed — a customer asking for help with a password got an
 * email that led nowhere. A code has no such dependency: the storefront's own
 * forgot-password page receives it, checks it, and writes the new password, and
 * so does the API for the frontend, through this same service.
 *
 * It is also how a guest's order becomes claimable. OrderPlacement creates an
 * account from the address an order was placed with, and that account has a
 * random password nobody was told — so the customer asking to get back into
 * their own order history is exactly the person this flow is for, and they
 * arrive at it with nothing but the address already on the order.
 */
class PasswordResetService
{
    /** The purpose the codes are issued under — see OtpService. */
    public const PURPOSE = 'password-reset';

    /**
     * Why a code was sent, in the customer's own terms. A guest's order account
     * is a different situation from a forgotten password, and the email text
     * shouldn't blur the two.
     */
    public const REASON_FORGOT = 'forgot';

    public const REASON_ORDER = 'order';

    public function __construct(
        private readonly OtpService $otp,
        private readonly ResetUserPassword $resetter,
    ) {}

    /**
     * Issues a code for an address and mails it, returning an OtpService
     * outcome: SENT, THROTTLED, RATE_LIMITED or FAILED.
     *
     * An address with no account is reported as SENT without anything being
     * mailed. The alternative — saying so — turns this endpoint into a way to
     * enumerate who has an account here, and the only thing it costs the honest
     * customer who mistyped their address is a code that never arrives.
     *
     * For the same reason the two cases have to be indistinguishable in every
     * other respect too, which is why an address with no account still spends
     * the hourly quota (see below) and still reports RATE_LIMITED once it is
     * spent. A quota that only applied to real accounts would turn "keep asking
     * until you're told to stop" into an oracle for whether the account exists.
     */
    public function requestCode(string $email, string $reason = self::REASON_FORGOT): string
    {
        $email = $this->normalize($email);
        $user = User::where('email', $email)->first();

        if (! $user) {
            return $this->spendQuota($email, $reason) ? OtpService::SENT : OtpService::RATE_LIMITED;
        }

        $status = $this->otp->issue(
            $email,
            self::PURPOSE,
            fn (string $code) => Mail::to($user->email)->send(new PasswordResetOtpMail($code, $user->name, $reason)),
        );

        if ($status === OtpService::FAILED) {
            // Nothing was cached (see OtpService::issue), so there is no code to
            // redeem — but the customer has been told one is coming, so the
            // reason has to reach whoever can fix the mail config.
            Log::error('Password reset code could not be emailed', [
                'email' => $email,
                'reason' => $reason,
            ]);
        }

        if ($status === OtpService::RATE_LIMITED) {
            $this->logRateLimited($email, $reason);
        }

        return $status;
    }

    /**
     * Counts a request that will not send anything against the address's hourly
     * quota, and says whether it was inside it. The counterpart to
     * OtpService::claimQuota for the branch where there is no account to mail.
     */
    private function spendQuota(string $email, string $reason): bool
    {
        if ($this->otp->claimQuota($email, self::PURPOSE)) {
            return true;
        }

        $this->logRateLimited($email, $reason);

        return false;
    }

    /**
     * Someone asking for more codes than a mailbox should ever be asked for is
     * either a customer locked out of their own account or someone using the
     * shop's mailer to fill somebody else's inbox, and the two need different
     * answers. It is worth a line in the log either way.
     */
    private function logRateLimited(string $email, string $reason): void
    {
        Log::warning('Password reset code requests rate limited', [
            'email' => $email,
            'reason' => $reason,
            'limit' => OtpService::MAX_ISSUES_PER_HOUR.' per '.OtpService::QUOTA_HOURS.'h',
        ]);
    }

    /**
     * Checks a code and, on a match, spends it. Returns an OtpService verify
     * outcome: VERIFIED, INVALID, LOCKED or EXPIRED.
     */
    public function verifyCode(string $email, string $code): string
    {
        return $this->otp->verify($email, self::PURPOSE, $code);
    }

    /**
     * Whether a code is still on file for this address — how the screen offering
     * "enter the code we sent" survives a refresh.
     */
    public function hasActiveCode(string $email): bool
    {
        return $this->otp->isActive($email, self::PURPOSE);
    }

    /**
     * The address as it is stored, so a code issued for it and one verified
     * against it are the same code however the customer typed it.
     */
    public function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * Writes a new password for an address, and marks that address verified.
     *
     * Reached only once a code has been redeemed: both the storefront controller
     * and the API controller gate this behind their own proof-of-inbox step (a
     * session flag, and a redeem in the same request), and neither takes the
     * address from anything the caller can edit between the two steps.
     *
     * @throws ValidationException when the new password doesn't meet the rules,
     *                             or the address has no account after all
     */
    public function setPassword(string $email, string $password, string $passwordConfirmation): User
    {
        $user = User::where('email', $this->normalize($email))->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('We could not find an account with that email address.'),
            ]);
        }

        $this->resetter->reset($user, [
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ]);

        // The code arrived in this address's inbox, which is the same proof of
        // ownership a verification link carries, so the address is marked
        // verified here too. Nothing enforces it today (User does not use
        // MustVerifyEmail), but leaving it unset would make this the one flow
        // that has actually demonstrated control of an inbox and then failed to
        // record it. forceFill because the column is not mass assignable.
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        // The reset is done, so nothing about it is left lying around: a second
        // request starts from a clean cooldown rather than waiting one out.
        $this->otp->forget($email, self::PURPOSE);

        return $user;
    }
}
