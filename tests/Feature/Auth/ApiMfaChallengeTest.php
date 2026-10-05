<?php

use App\Models\User;
use App\Support\ApiMfa;
use App\Support\Mfa;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Fortify\Features;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| The API's second-factor challenge
|--------------------------------------------------------------------------
|
| The customer API is the one login door with no session to hold a half-finished
| login in, so the state a challenge needs travels in an encrypted token handed
| to the client instead (App\Support\ApiMfa). That makes it the door where the
| interesting question is not "is the second factor checked" — it is Fortify's
| provider doing exactly what the web login does — but "is the token that names
| the account trustworthy, and short-lived".
|
| So most of what is proved here is about that token: that a password is enough
| to get one, that nothing in the answering request can choose whose account it
| is, and that it stops working the moment it should.
|
*/

beforeEach(function () {
    // Explicit rather than inherited from the default: these tests are about the
    // policy being ON, and "is it on by default" is a separate claim (asserted at
    // the bottom of this file) that should not be load-bearing here.
    //
    // The API is the customer door, so it is the customer role that asks for a
    // second factor here — the same switch an admin flips on Roles.
    $this->seed(RolePermissionSeeder::class);

    activateRoles('customer');

    Role::where('name', 'customer')->update(['mfa_enabled' => true]);
});

/**
 * A customer with a confirmed TOTP secret that actually generates codes, as
 * opposed to the factory's placeholder.
 *
 * Holding the customer role is what makes the role's MFA switch apply to them —
 * the API has no portal of its own to key off, so the account's roles are the
 * only thing that can say whether a second factor was asked for.
 */
function customerWithTotp(): User
{
    $user = User::factory()->create(['password' => 'secret123']);

    $user->assignRole('customer');

    $secret = (new Google2FA)->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-one', 'recovery-two'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

/** The code the authenticator app would be showing right now. */
function currentTotpCode(User $user): string
{
    return (new Google2FA)->getCurrentOtp(
        decrypt($user->fresh()->two_factor_secret)
    );
}

/** Log in and get back the challenge token the API issued. */
function apiChallengeFor(mixed $test, User $user): string
{
    return $test->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertOk()->json('data.mfa_token');
}

/**
 * Attach a passkey row for ceremony-generation tests.
 *
 * The id is real base64url because GenerateVerificationOptions decodes every
 * stored credential_id when it builds allowCredentials — a placeholder like
 * "test-credential-id" is fine for tests that never generate a ceremony and a
 * fatal for the ones that do.
 */
function attachFakePasskey(User $user, string $id = 'fake-passkey'): User
{
    $user->passkeys()->create([
        'name' => 'Test key',
        'credential_id' => Base64UrlSafe::encodeUnpadded($id),
        'credential' => ['type' => 'public-key'],
        'user_handle' => Base64UrlSafe::encodeUnpadded($user->email),
        'attestation_type' => 'none',
    ]);

    return $user->fresh();
}

/**
 * Stand in for a completed provider round-trip.
 *
 * Only the four calls findOrCreateUser() makes are stubbed, because that is all
 * the callback does with the provider's user — what is being tested here is what
 * happens *after* Socialite has done its job.
 */
function mockSocialUser(string $email, string $providerId): void
{
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getId')->andReturn($providerId);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->shouldReceive('getName')->andReturn('Jane Customer');
    $socialUser->shouldReceive('getNickname')->andReturn(null);

    $driver = Mockery::mock(Provider::class);
    $driver->shouldReceive('stateless')->andReturnSelf();
    $driver->shouldReceive('user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->andReturn($driver);
}

// ── The login that must not hand out a token ───────────────────────────────

it('withholds the token from an account with a second factor and answers with a challenge', function () {
    $user = customerWithTotp();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertOk()
        // No `token` key at all: a client that only checks data.token gets
        // undefined and falls into its signed-out path, which is the right
        // outcome. A "token": null would invite it to store that instead.
        ->assertJsonMissingPath('data.token')
        ->assertJsonPath('data.mfa_required', true)
        ->assertJsonPath('data.methods', [ApiMfa::METHOD_TOTP, ApiMfa::METHOD_RECOVERY_CODE])
        ->assertJsonStructure(['data' => ['mfa_token', 'methods', 'expires_in', 'user' => ['id', 'email']]]);

    // The strongest form of the assertion: nothing was minted at all.
    expect($user->tokens()->count())->toBe(0);
});

it('offers a passkey as an answer only when the account has one', function () {
    $this->skipUnlessFortifyHas(Features::passkeys());

    $user = attachFakePasskey(customerWithTotp(), 'api-passkey');

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertOk()
        ->assertJsonPath('data.methods', [
            ApiMfa::METHOD_TOTP,
            ApiMfa::METHOD_RECOVERY_CODE,
            ApiMfa::METHOD_PASSKEY,
        ]);
});

it('issues the token as before once the policy is switched off', function () {
    $user = customerWithTotp();

    Role::where('name', 'customer')->update(['mfa_enabled' => false]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertOk()
        ->assertJsonMissingPath('data.mfa_required')
        ->assertJsonStructure(['data' => ['token']]);
});

it('leaves an account with no second factor alone, because there is nothing to ask for', function () {
    // The one case where holding a token back would achieve nothing but a
    // locked-out customer: this account cannot answer a challenge, and the API is
    // not somewhere a factor can be enrolled. Documented on ApiMfa::isRequiredFor().
    $user = User::factory()->create(['password' => 'secret123']);

    expect(Mfa::hasFactorFor($user))->toBeFalse();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertOk()
        ->assertJsonMissingPath('data.mfa_required')
        ->assertJsonStructure(['data' => ['token']]);
});

it('never challenges on a wrong password, whoever the account is', function () {
    customerWithTotp();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

// ── Answering it ───────────────────────────────────────────────────────────

it('issues the token once a valid authenticator code is given', function () {
    $user = customerWithTotp();

    $token = apiChallengeFor($this, $user);

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => $token,
        'code' => currentTotpCode($user),
    ])->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['token', 'user']]);
});

it('accepts a recovery code as the answer', function () {
    $user = customerWithTotp();

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => apiChallengeFor($this, $user),
        'recovery_code' => 'recovery-one',
    ])->assertOk()
        ->assertJsonStructure(['data' => ['token']]);
});

it('refuses a wrong code without minting anything', function () {
    $user = customerWithTotp();

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => apiChallengeFor($this, $user),
        'code' => '000000',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');

    expect($user->tokens()->count())->toBe(0);
});

it('refuses a recovery code that is not one of the account\'s', function () {
    $user = customerWithTotp();

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => apiChallengeFor($this, $user),
        'recovery_code' => 'not-a-real-code',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('says one thing for every method, so a wrong answer teaches nothing', function () {
    $user = customerWithTotp();
    $token = apiChallengeFor($this, $user);

    $totp = $this->postJson('/api/v1/auth/mfa/verify', ['mfa_token' => $token, 'code' => '000000'])
        ->assertUnprocessable();
    $recovery = $this->postJson('/api/v1/auth/mfa/verify', ['mfa_token' => $token, 'recovery_code' => 'wrong'])
        ->assertUnprocessable();
    // A response body with the shape a real authenticator sends. It has to be
    // non-empty: `required_with` treats an empty array as missing, so an empty
    // one would be refused by validation and never reach the verifier this test
    // is about.
    $assertion = [
        'id' => 'x',
        'rawId' => 'x',
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => 'e30',
            'authenticatorData' => 'e30',
            'signature' => 'e30',
            'userHandle' => 'e30',
        ],
    ];

    $passkey = $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => $token,
        'credential' => $assertion,
    ])->assertUnprocessable();

    expect($totp->json('errors.code'))->toBe($recovery->json('errors.code'))
        ->and($recovery->json('errors.code'))->toBe($passkey->json('errors.code'));
});

it('refuses an answer that names no method at all', function () {
    $user = customerWithTotp();

    $this->postJson('/api/v1/auth/mfa/verify', ['mfa_token' => apiChallengeFor($this, $user)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'recovery_code', 'credential']);
});

// ── What the challenge token is trusted not to be ──────────────────────────

it('refuses a challenge token that was tampered with', function () {
    $user = customerWithTotp();

    $token = apiChallengeFor($this, $user);

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => substr($token, 0, -4).'AAAA',
        'code' => currentTotpCode($user),
    ])->assertUnprocessable()->assertJsonValidationErrors('mfa_token');

    expect($user->tokens()->count())->toBe(0);
});

it('refuses an encrypted payload that carries no challenge in it', function () {
    $user = customerWithTotp();

    // The token is not distinguished from other ciphertext by being encrypted —
    // anything running in this app can call encrypt(). What makes it a challenge
    // is the payload, and a payload with no expiry has to be refused rather than
    // treated as eternal, or "this challenge is good for five minutes" would be a
    // claim about tokens that happen to include one.
    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => encrypt(['user_id' => $user->id]),
        'code' => currentTotpCode($user),
    ])->assertUnprocessable()->assertJsonValidationErrors('mfa_token');

    expect($user->tokens()->count())->toBe(0);
});

it('refuses a challenge that has expired', function () {
    $user = customerWithTotp();

    $expired = encrypt([
        'user_id' => $user->id,
        'expires_at' => now()->subSecond()->getTimestamp(),
        'passkey_options' => null,
    ]);

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => $expired,
        'code' => currentTotpCode($user),
    ])->assertUnprocessable()->assertJsonValidationErrors('mfa_token');
});

it('refuses a challenge whose account was blocked after the password was accepted', function () {
    $user = customerWithTotp();

    $token = apiChallengeFor($this, $user);

    // The account being locked between the two halves must not be something a
    // second factor can talk its way past — the token re-checks rather than
    // trusting the state it was minted under.
    $user->forceFill(['is_blocked' => true])->save();

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => $token,
        'code' => currentTotpCode($user),
    ])->assertUnprocessable()->assertJsonValidationErrors('mfa_token');

    expect($user->tokens()->count())->toBe(0);
});

it('refuses a challenge whose account removed its factor after the password was accepted', function () {
    $user = customerWithTotp();

    $token = apiChallengeFor($this, $user);

    $user->forceFill([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->postJson('/api/v1/auth/mfa/verify', [
        'mfa_token' => $token,
        'code' => '000000',
    ])->assertUnprocessable()->assertJsonValidationErrors('mfa_token');
});

it('answers a challenge for a TOTP-only account without passkey options', function () {
    $user = customerWithTotp();

    $this->postJson('/api/v1/auth/mfa/options', ['mfa_token' => apiChallengeFor($this, $user)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mfa_token');
});

it('refreshes the ceremony for an account that has a passkey', function () {
    $this->skipUnlessFortifyHas(Features::passkeys());

    $user = attachFakePasskey(customerWithTotp(), 'refresh-passkey');

    $original = apiChallengeFor($this, $user);

    $response = $this->postJson('/api/v1/auth/mfa/options', ['mfa_token' => $original])
        ->assertOk()
        ->assertJsonStructure(['data' => ['mfa_token', 'passkey_options' => ['challenge']]]);

    // A different token, carrying the ceremony the response just handed over:
    // answering with the original would verify the assertion against the
    // expired challenge it was minted with.
    expect($response->json('data.mfa_token'))->not->toBe($original);
});

// ── The social door ────────────────────────────────────────────────────────

it('challenges a social sign-in too, since the provider never asks for a factor', function () {
    $user = customerWithTotp();

    mockSocialUser($user->email, 'google-123');

    $response = $this->get('/api/v1/auth/google/callback')->assertRedirectContains('error=mfa_required');

    expect($response->getTargetUrl())->toContain('mfa_token=')
        ->and($response->getTargetUrl())->toContain('methods=totp')
        ->and($user->tokens()->count())->toBe(0);
});

it('still hands a social sign-in straight through when the policy is off', function () {
    $user = customerWithTotp();

    Role::where('name', 'customer')->update(['mfa_enabled' => false]);

    mockSocialUser($user->email, 'google-456');

    $response = $this->get('/api/v1/auth/google/callback')
        ->assertRedirectContains('token=');

    expect($response->getTargetUrl())->not->toContain('error=mfa_required');

    expect($user->tokens()->count())->toBe(1);
});

// ── The default itself ─────────────────────────────────────────────────────

it('asks nobody for a second factor until a role says so', function () {
    // Proved on a role made right now rather than on the seeded ones, because the
    // seeded ones are switched on by this file's own beforeEach. A role an admin
    // adds, and the role an upgrade leaves behind, both come out of the same
    // column default — and that default has to be "nothing", or switching
    // enforcement on would happen to someone by installing an update.
    $fresh = Role::create(['name' => 'role_invented_after_the_upgrade', 'guard_name' => 'web']);

    expect((bool) $fresh->mfa_enabled)->toBeFalse()
        ->and((bool) $fresh->fresh()->mfa_enabled)->toBeFalse();
});
