<?php

use App\Livewire\Security\MfaPanel;
use App\Mail\MfaOtpMail;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Mfa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/** A user with the emailed code switched on and a half-finished login in the session. */
function challengedByEmailOtp(): User
{
    $user = User::factory()->create(['email_otp_enabled_at' => now()]);

    test()->withSession(['login.id' => $user->id]);

    return $user;
}

it('counts the emailed code as a second factor', function () {
    $user = User::factory()->create();

    expect(Mfa::hasFactorFor($user))->toBeFalse();

    $user->forceFill(['email_otp_enabled_at' => now()])->save();

    expect(Mfa::emailOtpEnabledFor($user))->toBeTrue()
        ->and(Mfa::hasFactorFor($user))->toBeTrue()
        // The API challenge has no way to send or type a mailed code.
        ->and(Mfa::hasApiFactorFor($user))->toBeFalse();
});

it('mails a code to the challenged account and signs them in with it', function () {
    Mail::fake();
    $user = challengedByEmailOtp();
    $sent = null;

    $this->post(route('two-factor.email-otp-send'))->assertRedirect();

    Mail::assertSent(MfaOtpMail::class, function (MfaOtpMail $mail) use ($user, &$sent) {
        $sent = $mail->code;

        return $mail->hasTo($user->email);
    });

    $this->post(route('two-factor.email-otp-verify'), ['email_code' => $sent])
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong code and does not sign in', function () {
    $user = challengedByEmailOtp();

    Cache::put('otp:'.Mfa::OTP_PURPOSE_LOGIN.':'.$user->email.':code', Hash::make('123456'), now()->addMinutes(5));

    $this->post(route('two-factor.email-otp-verify'), ['email_code' => '654321'])
        ->assertSessionHasErrors('email_code');

    $this->assertGuest();
});

it('refuses the emailed code for an account that has not turned it on', function () {
    Mail::fake();
    $user = User::factory()->withTwoFactor()->create();
    $this->withSession(['login.id' => $user->id]);

    $this->post(route('two-factor.email-otp-send'))->assertRedirect(route('two-factor.login'));

    Mail::assertNothingSent();
    $this->assertGuest();
});

it('turns the factor on only after the mailed code is typed back', function () {
    Mail::fake();
    $user = User::factory()->create();
    $this->actingAs($user);
    $sent = null;

    $component = Livewire::test(MfaPanel::class, ['confirmPassword' => false])
        ->call('startEmailOtpSetup')
        ->assertSet('emailOtpPending', true);

    Mail::assertSent(MfaOtpMail::class, function (MfaOtpMail $mail) use (&$sent) {
        $sent = $mail->code;

        return $mail->enrolling;
    });

    expect($user->fresh()->email_otp_enabled_at)->toBeNull();

    $component->set('emailCode', $sent)->call('confirmEmailOtp')
        ->assertSet('emailOtpEnabled', true)
        ->assertSet('emailOtpPending', false);

    expect(Mfa::emailOtpEnabledFor($user->fresh()))->toBeTrue();

    $component->call('disableEmailOtp')->assertSet('emailOtpEnabled', false);

    expect($user->fresh()->email_otp_enabled_at)->toBeNull();
});

it('does not turn the factor on with a wrong code', function () {
    Mail::fake();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(MfaPanel::class, ['confirmPassword' => false])
        ->call('startEmailOtpSetup')
        ->set('emailCode', '000000')
        ->call('confirmEmailOtp')
        ->assertHasErrors('emailCode');

    expect($user->fresh()->email_otp_enabled_at)->toBeNull()
        ->and(app(OtpService::class)->isActive($user->email, Mfa::OTP_PURPOSE_ENROL))->toBeTrue();
});
