<?php

namespace App\Http\Controllers;

use App\Enums\TipoSolicitud;
use App\Http\Requests\CambioNombreRequest;
use App\Http\Requests\StoreSolicitudRequest;
use App\Models\SolicitudEmprendedor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SolicitudEmprendedorController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Emprendedor/Solicitud');
    }

    public function store(StoreSolicitudRequest $request): RedirectResponse
    {
        $request->user()->solicitudes()->create($request->validated());
        Inertia::flash('exito', 'Recibimos tu solicitud. Te avisamos desde tu perfil.');

        return to_route('perfil');
    }

    /** El nombre público no cambia hasta que un moderador apruebe la solicitud. */
    public function cambiarNombre(CambioNombreRequest $request): RedirectResponse
    {
        $solicitud = new SolicitudEmprendedor([
            'nombre_emprendimiento' => $request->validated('nombre_emprendimiento'),
            'descripcion' => $request->validated('motivo'),
        ]);
        $solicitud->tipo = TipoSolicitud::CambioNombre;
        $request->user()->solicitudes()->save($solicitud);

        Inertia::flash('exito', 'Pediste el cambio de nombre. Lo revisa un moderador.');

        return back();
    }
}
