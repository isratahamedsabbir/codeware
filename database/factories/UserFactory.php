<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Assigns the 'admin' Spatie role (every permission — see
     * RolePermissionSeeder) once the user is actually persisted, since
     * assignRole() needs a real id for the role pivot.
     */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('admin'));
    }

    /**
     * Assigns the 'staff' Spatie role (content-only permissions — see
     * RolePermissionSeeder), switching the role on first.
     *
     * RolePermissionSeeder ships 'staff' inactive like every other non-admin
     * tier, and access-admin refuses a holder of an inactive role (AppServiceProvider
     * / EnsureUserIsNotBlocked). Assigning the role alone would therefore hand
     * back a user the admin panel rejects, so this state stands in for the admin
     * who has already enabled the tier from Admin → Roles — which is the whole
     * point of the default.
     */
    public function staff(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::where('name', 'staff')->where('guard_name', 'web')->update(['status' => 'active']);

            $user->assignRole('staff');
        });
    }
}
