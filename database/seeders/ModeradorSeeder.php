<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class ModeradorSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('MODERADOR_EMAIL');
        $password = env('MODERADOR_PASSWORD');

        if (! $email || ! $password) {
            $this->command->warn('Sin MODERADOR_EMAIL o MODERADOR_PASSWORD: no se crea el moderador.');

            return;
        }

        User::firstOrCreate(['email' => $email], ['name' => 'Moderación', 'password' => $password])
            ->forceFill(['rol' => Rol::Moderador, 'email_verified_at' => now()])
            ->save();
    }
}
