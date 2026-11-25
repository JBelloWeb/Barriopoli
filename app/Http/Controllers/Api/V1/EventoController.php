<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Eventos\CancelarEvento;
use App\Actions\Eventos\GuardarEvento;
use App\Enums\EstadoEvento;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EventoController as WebEventoController;
use App\Http\Requests\EventoRequest;
use App\Http\Resources\EventoResource;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class EventoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'barrio' => ['nullable', 'string', 'exists:barrios,slug'],
            'categorias' => ['array'],
            'categorias.*' => ['integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'emprendimiento' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $eventos = Evento::publicados()
            ->with(WebEventoController::RELACIONES)
            ->when(
                $filtros['desde'] ?? null,
                fn ($query, $desde) => $query->whereRaw('COALESCE(eventos.fin, eventos.inicio) >= ?', [Carbon::parse($desde)]),
                fn ($query) => $query->vigentes(),
            )
            ->when($filtros['hasta'] ?? null, fn ($query, $hasta) => $query->where('inicio', '<=', Carbon::parse($hasta)->endOfDay()))
            ->when($filtros['barrio'] ?? null, fn ($query, $slug) => $query->whereHas('barrio', fn ($barrio) => $barrio->where('slug', $slug)))
            ->when($filtros['categorias'] ?? null, fn ($query, $ids) => $query->whereIn('categoria_id', $ids))
            ->when($filtros['emprendimiento'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->orderBy('inicio')
            ->paginate($filtros['per_page'] ?? 15)
            ->withQueryString();

        return EventoResource::collection($eventos);
    }

    public function show(Evento $evento): EventoResource
    {
        abort_unless($evento->estado === EstadoEvento::Publicado, 404);

        return EventoResource::make($evento->load(WebEventoController::RELACIONES));
    }

    public function misEventos(Request $request): AnonymousResourceCollection
    {
        $eventos = $request->user()->esModerador()
            ? Evento::query()
            : $request->user()->eventos();

        return EventoResource::collection(
            $eventos->with(WebEventoController::RELACIONES)->latest('inicio')->paginate(15)
        );
    }

    public function store(EventoRequest $request, GuardarEvento $guardar): JsonResponse
    {
        $evento = $guardar->crear($request)->load(WebEventoController::RELACIONES);

        return EventoResource::make($evento)
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.eventos.show', $evento));
    }
    
    public function update(EventoRequest $request, Evento $evento, GuardarEvento $guardar): EventoResource
    {
        return EventoResource::make($guardar->actualizar($request, $evento)->load(WebEventoController::RELACIONES));
    }

    public function destroy(Evento $evento, CancelarEvento $cancelar): Response
    {
        Gate::authorize('delete', $evento);
        $cancelar($evento);

        return response()->noContent();
    }
}
