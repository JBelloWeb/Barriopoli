<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
     * Rol (y campos protegidos) después de crear: `rol` y `emprendimiento` no son fillable.
     *
     * @param  array<string, mixed>  $extra
     */
    public function conRol(Rol $rol, array $extra = []): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['rol' => $rol, ...$extra])->save());
    }

    public function moderador(): static
    {
        return $this->conRol(Rol::Moderador);
    }

    public function emprendedor(string $emprendimiento = 'Feria del Parque'): static
    {
        return $this->conRol(Rol::Emprendedor, ['emprendimiento' => $emprendimiento]);
    }
}
