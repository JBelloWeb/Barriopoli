<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Enums\TipoSolicitud;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Perfil/Show', [
            'solicitud' => $user->solicitudes()->where('tipo', TipoSolicitud::Alta)->latest()->first(['estado', 'motivo_rechazo']),
            'emprendimiento' => $user->emprendimiento,
            // Con geojson: el perfil dibuja la silueta de Mi barrio (PLAN-07).
            'miBarrio' => $user->barrioPropio()->first(['id', 'nombre', 'slug', 'comuna', 'geojson']),
            'cantidades' => [
                'favoritos' => $user->barriosFavoritos()->count(),
                'agendados' => $user->eventosAgendados()->publicados()->vigentes()->count(),
            ],
        ]);
    }

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Perfil/Editar', [
            'emprendimiento' => $user->rol === Rol::Emprendedor ? $user->emprendimiento : null,

            'cambioNombre' => $user->rol === Rol::Emprendedor
                ? $user->solicitudes()
                    ->where('tipo', TipoSolicitud::CambioNombre)
                    ->latest('id')
                    ->first(['estado', 'nombre_emprendimiento', 'motivo_rechazo'])
                : null,
        ]);
    }
}
