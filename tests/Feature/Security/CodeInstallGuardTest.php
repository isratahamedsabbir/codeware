<?php

use App\Models\User;
use App\Support\CodeInstall;
use App\Support\Mfa;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

it('refuses all code installs when uploads are disabled', function () {
    config(['security.code_install.allowed' => false]);

    expect(CodeInstall::refusal('whatever'))->toContain('disabled')
        ->and(CodeInstall::refusal(null, needsPassword: false))->toContain('disabled');
});

it('wants the current password before installing', function () {
    config(['security.code_install.allowed' => true]);
    $this->actingAs(User::factory()->create(['password' => 'right-Passw0rd!']));

    expect(CodeInstall::refusal('wrong'))->toContain('password')
        ->and(CodeInstall::refusal(''))->toContain('password')
        ->and(CodeInstall::refusal('right-Passw0rd!'))->toBeNull();
});

it('only accepts packages on the checksum allowlist when one is set', function () {
    config(['security.code_install.allowed' => true]);
    $this->actingAs(User::factory()->create(['password' => 'right-Passw0rd!']));

    $zip = tempnam(sys_get_temp_dir(), 'pkg');
    file_put_contents($zip, 'package-bytes');

    config(['security.code_install.sha256' => [str_repeat('0', 64)]]);
    expect(CodeInstall::refusal('right-Passw0rd!', $zip))->toContain('checksums');

    config(['security.code_install.sha256' => [hash_file('sha256', $zip)]]);
    expect(CodeInstall::refusal('right-Passw0rd!', $zip))->toBeNull();
});

it('forces admins without a second factor into enrolment when required', function () {
    Role::findOrCreate('admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    config(['security.require_admin_mfa' => false]);
    expect(Mfa::needsEnrolmentFor($admin))->toBeFalse();

    config(['security.require_admin_mfa' => true]);

    // The role switch (Admin -> Roles) decides: off means nothing is asked...
    Role::where('name', 'admin')->update(['mfa_enabled' => false]);
    expect(Mfa::needsEnrolmentFor($admin->fresh()))->toBeFalse()
        ->and(Mfa::isRequiredFor($admin->fresh()))->toBeFalse();

    // ...on means enrolment is mandatory for the admin role.
    Role::where('name', 'admin')->update(['mfa_enabled' => true]);
    expect(Mfa::needsEnrolmentFor($admin->fresh()))->toBeTrue()
        ->and(Mfa::isRequiredFor($admin->fresh()))->toBeTrue();

    $customer = User::factory()->create();
    expect(Mfa::needsEnrolmentFor($customer))->toBeFalse();
});

it('treats an unrecognised APP_ENV like production for password rules', function () {
    $this->app['env'] = 'developer';

    $rule = Password::default();

    expect($rule)->not->toBeNull()
        ->and(Validator::make(['p' => 'Short1!'], ['p' => [Password::default()]])->fails())->toBeTrue();
});
