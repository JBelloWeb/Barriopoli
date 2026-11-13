<?php

namespace App\Http\Controllers;

use App\Models\Barrio;
use App\Models\Categoria;
use Inertia\Inertia;
use Inertia\Response;

class BarrioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Barrios/Index', [
            'barrios' => $this->listaBarrios(),
        ]);
    }

    public function show(string $slug): Response
    {
        $barrio = Barrio::where('slug', $slug)->firstOrFail();

        $eventosGeoJson = [
            'type' => 'FeatureCollection',
            'features' => $barrio->eventos()->with('categoria')->get()->map(fn ($evento) => [
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
                    'categoria_id' => $evento->categoria_id,
                    'categoria' => $evento->categoria->nombre,
                    'icono' => $evento->categoria->icono,
                    'color' => $evento->categoria->color,
                ],
            ])->values(),
        ];

        return Inertia::render('Barrios/Show', [
            'barrio' => $barrio,
            'eventosGeoJson' => $eventosGeoJson,
            // El buscador del layout también la usa en esta página.
            'barrios' => $this->listaBarrios(),
            'categorias' => Inertia::once(fn () => Categoria::activas()->get(['id', 'nombre', 'color', 'icono'])),
        ]);
    }

    /**
     * Lista para el buscador. `once`: el cliente la conserva y el servidor no
     * la reenvía al navegar entre barrios (los 48 barrios no cambian).
     */
    private function listaBarrios()
    {
        return Inertia::once(fn () => Barrio::orderBy('nombre')->get(['id', 'nombre', 'slug', 'comuna']));
    }
}
