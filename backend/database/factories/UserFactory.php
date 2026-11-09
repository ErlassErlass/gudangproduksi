<?php

namespace Database\Factories;

use App\Domains\Administration\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withRole(string $roleCode): static
    {
        return $this->afterCreating(function (User $user) use ($roleCode): void {
            $role = Role::query()->firstOrCreate(
                ['code' => $roleCode],
                ['name' => Str::headline($roleCode), 'is_system' => true],
            );
            $user->roles()->syncWithoutDetaching([$role->id]);
            $user->flushPermissionCache();
        });
    }
}
