<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['Música', '#c2410c', '🎵'],
            ['Teatro', '#7c3aed', '🎭'],
            ['Cine', '#1d4ed8', '🎬'],
            ['Arte', '#be185d', '🎨'],
            ['Feria', '#15803d', '🛍️'],
            ['Gastronomía', '#b45309', '🍽️'],
            ['Taller', '#0f766e', '🧵'],
            ['Deporte', '#0369a1', '⚽'],
            ['Patrimonio', '#92400e', '🏛️'],
            ['Comunidad', '#4f46e5', '🤝'],
            ['Otros', '#4b5563', '📌'],
        ];

        foreach ($categorias as $orden => [$nombre, $color, $icono]) {
            Categoria::updateOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre, 'color' => $color, 'icono' => $icono, 'orden' => $orden],
            );
        }
    }
}
