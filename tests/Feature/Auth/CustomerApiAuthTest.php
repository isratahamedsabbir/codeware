<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

// Customer accounts are API-only — register/login/logout/password-reset/email
// verification all live under /api/v1/auth, backed by the same `users` table
// the admin/Fortify web login uses (is_admin stays false for these accounts).

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
    expect((bool) $user->is_admin)->toBeFalse();

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

it('sends a password reset link for a known email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets a customer\'s password with a valid token', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertOk();

        return true;
    });

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'brand-new-password',
    ])->assertOk();
});

it('rejects password reset with an invalid token', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertUnprocessable();
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

it('ignores an is_admin field sent to registration and never grants admin', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Sneaky Customer',
        'email' => 'sneaky@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_admin' => true,
    ])->assertCreated();

    $user = User::where('email', 'sneaky@example.com')->sole();
    expect((bool) $user->is_admin)->toBeFalse();
});

it('rejects a customer account from every admin API endpoint', function () {
    $user = User::factory()->create(['password' => 'secret123']);
    expect((bool) $user->is_admin)->toBeFalse();

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
