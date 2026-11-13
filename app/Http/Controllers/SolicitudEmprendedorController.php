<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolicitudRequest;
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
}
