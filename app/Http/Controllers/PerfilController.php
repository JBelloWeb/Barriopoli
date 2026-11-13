<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Perfil/Show', [
            'solicitud' => $request->user()->solicitudes()->latest()->first(['estado', 'motivo_rechazo']),
        ]);
    }
}
