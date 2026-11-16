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
            // Tokens de la API (solo quien publica): nunca el texto plano, solo metadatos.
            'tokens' => $user->puedePublicar()
                ? $user->tokens()
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->latest()
                    ->get(['id', 'name', 'last_used_at', 'expires_at'])
                    ->map(fn ($token) => [
                        'id' => $token->id,
                        'nombre' => $token->name,
                        'ultimo_uso' => $token->last_used_at?->toIso8601String(),
                        'expira' => $token->expires_at?->toIso8601String(),
                    ])
                : [],
        ]);
    }
}
