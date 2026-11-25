<?php

namespace App\Http\Controllers\Moderacion;

use App\Enums\EstadoEvento;
use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventoController extends Controller
{
    public function aprobar(Request $request, Evento $evento): RedirectResponse
    {
        abort_unless($evento->estado === EstadoEvento::Pendiente, 409);

        if ($evento->latitud === null || $evento->longitud === null) {
            Inertia::flash('error', 'Falta la ubicación: el autor tiene que marcarla antes de publicarlo.');

            return back();
        }

        $evento->forceFill([
            'estado' => EstadoEvento::Publicado,
            'motivo_rechazo' => null,
            'revisado_por' => $request->user()->id,
            'revisado_at' => now(),
        ])->save();

        Inertia::flash('exito', 'Evento publicado.');

        return back();
    }

    public function rechazar(Request $request, Evento $evento): RedirectResponse
    {
        abort_unless($evento->estado === EstadoEvento::Pendiente, 409);

        $datos = $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        $evento->forceFill([
            'estado' => EstadoEvento::Rechazado,
            'motivo_rechazo' => $datos['motivo'],
            'revisado_por' => $request->user()->id,
            'revisado_at' => now(),
        ])->save();

        Inertia::flash('exito', 'Evento rechazado.');

        return back();
    }
}
