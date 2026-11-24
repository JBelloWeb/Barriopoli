<?php

use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

it('los seeders de instalación dejan la app lista para usar', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Barrio::count())->toBe(48)
        ->and(Categoria::where('activa', true)->count())->toBeGreaterThan(0)
        ->and(User::where('rol', Rol::Emprendedor)->count())->toBe(2)
        ->and(Evento::publicados()->vigentes()->count())->toBeGreaterThan(0);

    // Cada barrio tiene su geometría: sin ella la detección espacial no encuentra nada.
    expect(Barrio::queContiene(-34.5807, -58.4209)->value('slug'))->toBe('palermo');
});
