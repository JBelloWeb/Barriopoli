<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEvento;
use App\Http\Resources\EventoResource;
use App\Models\Evento;
use App\Services\CalendarioIcs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $vista = $request->query('vista') === 'pasados' ? 'pasados' : 'proximos';

        $eventos = $user->agendaVisible($vista)->with(EventoController::RELACIONES);

        $avisos = $user->unreadNotifications()->latest()->get()->map(fn ($aviso) => [
            'id' => $aviso->id,
            ...$aviso->data,
        ]);
        // Se marcan leídos acá: las props compartidas se evalúan después y la insignia vuelve a 0.
        $user->unreadNotifications()->update(['read_at' => now()]);

        return Inertia::render('Agenda/Index', [
            'eventos' => EventoResource::collection($eventos->limit(100)->get())->resolve(),
            'vista' => $vista,
            'avisos' => $avisos,
        ]);
    }

    public function update(Request $request, Evento $evento): RedirectResponse
    {
        abort_unless($evento->estado === EstadoEvento::Publicado, 403);

        $request->user()->eventosAgendados()->syncWithoutDetaching([$evento->id]);

        return back();
    }

    /** int y no Evento: el route model binding excluye los cancelados y no se podrían quitar. */
    public function destroy(Request $request, int $evento): RedirectResponse
    {
        $request->user()->eventosAgendados()->detach($evento);

        return back();
    }

    public function exportarEvento(Evento $evento, CalendarioIcs $calendario): HttpResponse
    {
        Gate::authorize('view', $evento);
        $evento->load('barrio:id,nombre');

        return $this->descargar($calendario->generar([$evento]), Str::slug($evento->titulo) ?: 'evento');
    }

    public function exportarAgenda(Request $request, CalendarioIcs $calendario): HttpResponse
    {
        $eventos = $request->user()->eventosAgendados()
            ->publicados()
            ->vigentes()
            ->with('barrio:id,nombre')
            ->orderBy('inicio')
            ->get();

        return $this->descargar($calendario->generar($eventos), 'agenda-barriopoli');
    }

    private function descargar(string $contenido, string $nombre): HttpResponse
    {
        return response($contenido, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$nombre}.ics\"",
        ]);
    }
}
