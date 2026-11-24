<?php

namespace App\Http\Controllers\Moderacion;

use App\Enums\EstadoEvento;
use App\Enums\EstadoSolicitud;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EventoController;
use App\Http\Resources\EventoResource;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\SolicitudEmprendedor;
use Inertia\Inertia;
use Inertia\Response;

class PanelController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Moderacion/Index', [
            'solicitudes' => SolicitudEmprendedor::with('user:id,name,email,emprendimiento')
                ->where('estado', EstadoSolicitud::Pendiente)
                ->oldest()
                ->get(),
            'categorias' => Categoria::orderBy('orden')->orderBy('nombre')->get(),
            'eventosPendientes' => EventoResource::collection(
                Evento::where('estado', EstadoEvento::Pendiente)
                    ->with(EventoController::RELACIONES)
                    ->oldest()
                    ->get()
            )->resolve(),
        ]);
    }
}
