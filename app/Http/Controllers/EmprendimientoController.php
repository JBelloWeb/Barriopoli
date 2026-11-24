<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventoResource;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/** Perfil público del emprendedor: su nombre y sus eventos próximos. Nunca nombre real ni email. */
class EmprendimientoController extends Controller
{
    public function show(int $emprendedor): Response
    {
        $usuario = User::emprendimientosPublicos()
            ->with(['eventos' => fn ($query) => $query->publicados()->vigentes()
                ->with(EventoController::RELACIONES)
                ->orderBy('inicio')
                ->limit(50)])
            ->findOrFail($emprendedor, ['id', 'emprendimiento']);

        return Inertia::render('Emprendimientos/Show', [
            'emprendimiento' => ['id' => $usuario->id, 'nombre' => $usuario->emprendimiento],
            'eventos' => EventoResource::collection($usuario->eventos)->resolve(),
        ]);
    }
}
