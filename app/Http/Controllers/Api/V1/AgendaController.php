<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoEvento;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EventoController as WebEventoController;
use App\Http\Resources\EventoResource;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AgendaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate(['vista' => ['nullable', 'in:proximos,pasados']]);

        return EventoResource::collection(
            $request->user()->agendaVisible($datos['vista'] ?? 'proximos')
                ->with(WebEventoController::RELACIONES)
                ->paginate(15)
                ->withQueryString()
        );
    }

    public function update(Request $request, Evento $evento): Response
    {
        abort_unless($evento->estado === EstadoEvento::Publicado, 403, 'Solo se pueden agendar eventos publicados.');

        $request->user()->eventosAgendados()->syncWithoutDetaching([$evento->id]);

        return response()->noContent();
    }

    /** int y no Evento: el route model binding excluye los cancelados y no se podrían quitar. */
    public function destroy(Request $request, int $evento): Response
    {
        $request->user()->eventosAgendados()->detach($evento);

        return response()->noContent();
    }
}
