<?php

namespace Database\Seeders;

use App\Models\Barrio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BarrioSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $datos = json_decode(File::get(database_path('data/caba_barrios.geojson')), true);

        foreach ($datos['features'] as $feature) {
            Barrio::create([
                'nombre' => $feature['properties']['BARRIO'],
                'slug' => Str::slug($feature['properties']['BARRIO']),
                'comuna' => (int) $feature['properties']['COMUNA'],
                'geojson' => $feature,
            ]);
        }
    }
}
