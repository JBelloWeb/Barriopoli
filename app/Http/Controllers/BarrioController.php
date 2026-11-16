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

        // Solo publicados y vigentes: un pendiente o con coordenadas nulas nunca llega al mapa.
        $eventos = $barrio->eventos()
            ->publicados()
            ->vigentes()
            ->with(EventoController::RELACIONES)
            ->orderBy('inicio')
            ->get();

        return Inertia::render('Barrios/Show', [
            'barrio' => $barrio,
            // El MapaLayout arma el GeoJSON de los pines y abre el detalle en el bottom sheet.
            'eventos' => EventoResource::collection($eventos)->resolve(),
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
