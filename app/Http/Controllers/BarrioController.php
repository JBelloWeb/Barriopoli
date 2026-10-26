<?php

namespace App\Http\Controllers;

use App\Models\Barrio;
use Inertia\Inertia;
use Inertia\Response;

class BarrioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Barrios/Index', [
            'barrios' => Barrio::orderBy('comuna')->get(['id', 'nombre', 'slug', 'comuna']),
        ]);
    }

  public function show(string $slug)
{
    $barrio = Barrio::where('slug', $slug)->firstOrFail();

    // Obtener los eventos del barrio (retorna colección vacía si no hay registros)
    $eventos = $barrio->eventos; 

    $eventosGeoJson = [
        'type' => 'FeatureCollection',
        'features' => $eventos->map(function ($evento) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $evento->longitud, (float) $evento->latitud],
                ],
                'properties' => [
                    'id' => $evento->id,
                    'titulo' => $evento->titulo,
                    'direccion' => $evento->direccion,
                    'fecha' => $evento->fecha,
                ],
            ];
        })->values(), // values() asegura que el array se mantenga ordenado e indexado para JSON
    ];

    return Inertia::render('Barrios/Show', [
        'barrio' => $barrio,
        'eventosGeoJson' => $eventosGeoJson, // Siempre enviará la clave, aunque features sea []
    ]);
}
}
