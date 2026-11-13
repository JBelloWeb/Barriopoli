<?php

namespace App\Http\Controllers\Moderacion;

use App\Enums\EstadoSolicitud;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\SolicitudEmprendedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SolicitudController extends Controller
{
    public function aprobar(Request $request, SolicitudEmprendedor $solicitud): RedirectResponse
    {
        abort_unless($solicitud->estado === EstadoSolicitud::Pendiente, 409);

        DB::transaction(function () use ($request, $solicitud) {
            $solicitud->forceFill([
                'estado' => EstadoSolicitud::Aprobada,
                'revisada_por' => $request->user()->id,
                'revisada_at' => now(),
            ])->save();

            $solicitud->user->forceFill([
                'rol' => Rol::Emprendedor,
                'emprendimiento' => $solicitud->nombre_emprendimiento,
            ])->save();
        });

        Inertia::flash('exito', 'Solicitud aprobada.');

        return back();
    }

    public function rechazar(Request $request, SolicitudEmprendedor $solicitud): RedirectResponse
    {
        abort_unless($solicitud->estado === EstadoSolicitud::Pendiente, 409);

        $datos = $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        $solicitud->forceFill([
            'estado' => EstadoSolicitud::Rechazada,
            'motivo_rechazo' => $datos['motivo'],
            'revisada_por' => $request->user()->id,
            'revisada_at' => now(),
        ])->save();

        Inertia::flash('exito', 'Solicitud rechazada.');

        return back();
    }
}
