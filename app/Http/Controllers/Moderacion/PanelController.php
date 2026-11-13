<?php

namespace App\Http\Controllers\Moderacion;

use App\Enums\EstadoSolicitud;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\SolicitudEmprendedor;
use Inertia\Inertia;
use Inertia\Response;

class PanelController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Moderacion/Index', [
            'solicitudes' => SolicitudEmprendedor::with('user:id,name,email')
                ->where('estado', EstadoSolicitud::Pendiente)
                ->oldest()
                ->get(),
            'categorias' => Categoria::orderBy('orden')->orderBy('nombre')->get(),
            // PLAN-03 agrega 'eventosPendientes'.
        ]);
    }
}
