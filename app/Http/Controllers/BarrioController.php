<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventoResource;
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

        $eventos = $barrio->eventos()
            ->publicados()
            ->vigentes()
            ->with(EventoController::RELACIONES)
            ->orderBy('inicio')
            ->get();

        return Inertia::render('Barrios/Show', [
            'barrio' => $barrio,
            'eventos' => EventoResource::collection($eventos)->resolve(),
            'barrios' => $this->listaBarrios(),
            'categorias' => Inertia::once(fn () => Categoria::activas()->get(['id', 'nombre', 'color', 'icono'])),
        ]);
    }

    private function listaBarrios()
    {
        return Inertia::once(fn () => Barrio::orderBy('nombre')->get(['id', 'nombre', 'slug', 'comuna']));
    }
}
