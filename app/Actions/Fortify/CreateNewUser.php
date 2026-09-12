<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        // Every user belongs to some role (admin/staff/vendor/customer) — this is
        // the default tier for anyone signing up through the public site or API,
        // shared by both registration paths since both funnel through here.
        // findOrCreate rather than a bare name lookup so this never depends on
        // RolePermissionSeeder having already run in this environment/test.
        $user->assignRole(Role::findOrCreate('customer', 'web'));

        return $user;
    }
}
