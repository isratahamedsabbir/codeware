<?php

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

// Customer accounts are API-only — register/login/logout/password-reset/email
// verification all live under /api/v1/auth, backed by the same `users` table
// the admin/Fortify web login uses (no admin/staff role for these accounts).
// Password reset is a mailed code redeemed in the same call, not a token link —
// see App\Http\Controllers\Api\V1\Auth\PasswordResetController.

it('registers a new customer, issues a token, and sends a verification email', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Customer',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    $response->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);

    $user = User::where('email', 'jane@example.com')->sole();
    expect($user->hasRole('customer'))->toBeTrue();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('logs a customer in with correct credentials and returns a token', function () {
    $user = User::factory()->create(['email' => 'login@example.com', 'password' => 'secret123']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'secret123',
    ])->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['token']]);
});

it('rejects login with an incorrect password', function () {
    User::factory()->create(['email' => 'login2@example.com', 'password' => 'secret123']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login2@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('logs a customer out by revoking the current token', function () {
    $user = User::factory()->create(['password' => 'secret123']);

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->json('data.token');

    expect($user->tokens()->count())->toBe(1);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    // Asserted against the DB rather than a follow-up authenticated request: Sanctum's
    // guard memoizes the resolved user for the request/container lifetime, and Pest's
    // in-process test calls share that container, so a second call here would still
    // see the already-resolved (pre-revocation) user rather than re-checking the token.
    expect($user->tokens()->count())->toBe(0);
});

it('sends a reset code to a known email', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('data.message', 'If that email address has an account, a code is on its way.');

    Mail::assertSent(PasswordResetOtpMail::class, fn (PasswordResetOtpMail $mail) => $mail->hasTo($user->email));
});

it('answers the same for an email with no account, and sends nothing', function () {
    Mail::fake();
    $user = User::factory()->create();

    $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
    $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

    // An endpoint that answered differently for the two would let anyone find
    // out who has an account here.
    expect($unknown->json('data.message'))->toBe($known->json('data.message'));

    Mail::assertSent(PasswordResetOtpMail::class, 1);
});

it('resets a customer\'s password with the emailed code, in one call', function () {
    Mail::fake();
    $user = User::factory()->create(['password' => 'the-old-password']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    $code = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => $code,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'brand-new-password',
    ])->assertOk();
});

it('marks the address verified, having proven control of its inbox', function () {
    Mail::fake();
    $user = User::factory()->unverified()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);
    $code = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => $code,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('rejects password reset with an incorrect code', function () {
    Mail::fake();
    $user = User::factory()->create(['password' => 'the-old-password']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => '000000',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');

    expect(Hash::check('the-old-password', $user->fresh()->password))->toBeTrue();
});

it('will not accept the same code twice', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);
    $code = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $payload = [
        'email' => $user->email,
        'code' => $code,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ];

    $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
    $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('leaves the code already in the inbox usable when another is asked for straight away', function () {
    Mail::fake();
    $user = User::factory()->create(['password' => 'the-old-password']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
    $code = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    // Same answer as the first call, because the same thing is true: a code is
    // with them. A "come back later" error would be the API client looking for a
    // wait that buys it nothing.
    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    Mail::assertSent(PasswordResetOtpMail::class, 1);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => $code,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();
});

it('refuses a weak password without spending the code', function () {
    Mail::fake();
    $user = User::factory()->create(['password' => 'the-old-password']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);
    $code = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    // The customer gets one code per minute. Spending it on a password that was
    // going to be refused would cost them the chance to fix their own typo.
    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => $code,
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => $code,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();
});

it('requires an email and a code to reset a password', function () {
    $this->postJson('/api/v1/auth/reset-password', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'code', 'password']);
});

it('verifies a customer\'s email via the signed link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.v1.auth.email.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->getJson($url)->assertOk();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects email verification with a tampered hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.v1.auth.email.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('someone-else@example.com')],
    );

    $this->getJson($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email for an authenticated but unverified customer', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create(['password' => 'secret123']);

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->json('data.token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/email/resend')
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
});

// --- Admin can't be bypassed through this API ---

it('rejects a customer account from every admin API endpoint', function () {
    $user = User::factory()->create(['password' => 'secret123']);
    expect($user->hasRole('admin'))->toBeFalse();
    expect($user->hasRole('staff'))->toBeFalse();

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->json('data.token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/admin/products')
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/admin/posts')
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/admin/pages')
        ->assertForbidden();
});

// --- Rate limiting ---

it('throttles registration attempts', function () {
    foreach (range(1, 6) as $i) {
        $this->postJson('/api/v1/auth/register', [
            'name' => "Flooder {$i}",
            'email' => "flooder{$i}@example.com",
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();
    }

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Flooder 7',
        'email' => 'flooder7@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(429);
});

it('throttles login attempts per email+IP', function () {
    $user = User::factory()->create(['password' => 'secret123']);

    // 3 wrong-password attempts allowed (see FortifyServiceProvider), then locked
    // out for 5 minutes — shared with Fortify's own web login via the same
    // 'login' named rate limiter.
    foreach (range(1, 3) as $_) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

it('throttles forgot-password requests per IP', function () {
    Notification::fake();
    // Distinct emails per request: the password broker itself already imposes a
    // 60s per-email cooldown (config/auth.php `passwords.users.throttle`), so
    // reusing one email would trip that instead of the route's own IP throttle.
    $users = User::factory()->count(7)->create();

    foreach ($users->take(6) as $user) {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
    }

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $users->last()->email])
        ->assertStatus(429);
});
