<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Perfil/Show', [
            'solicitud' => $user->solicitudes()->latest()->first(['estado', 'motivo_rechazo']),
            'emprendimiento' => $user->emprendimiento,
            // Con geojson: el perfil dibuja la silueta de Mi barrio (PLAN-07).
            'miBarrio' => $user->barrioPropio()->first(['id', 'nombre', 'slug', 'comuna', 'geojson']),
            'cantidades' => [
                'favoritos' => $user->barriosFavoritos()->count(),
                'agendados' => $user->eventosAgendados()->publicados()->vigentes()->count(),
            ],
        ]);
    }
}
