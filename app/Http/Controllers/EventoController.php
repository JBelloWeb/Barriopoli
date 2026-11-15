<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEvento;
use App\Http\Requests\EventoRequest;
use App\Http\Resources\EventoResource;
use App\Models\Categoria;
use App\Models\Evento;
use App\Notifications\CambioEnEvento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventoController extends Controller
{
    public const RELACIONES = ['categoria', 'barrio:id,nombre,slug', 'autor:id,name,emprendimiento'];

    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'categorias' => ['array'],
            'categorias.*' => ['integer'],
            'alcance' => ['nullable', 'in:todos,mis_barrios'], // lo usa PLAN-04
        ]);

        $eventos = Evento::publicados()
            ->vigentes()
            ->with(self::RELACIONES)
            ->when($filtros['categorias'] ?? null, fn ($query, $ids) => $query->whereIn('categoria_id', $ids))
            ->when(
                ($filtros['alcance'] ?? null) === 'mis_barrios' && $request->user(),
                fn ($query) => $query->whereIn('barrio_id', $request->user()->barriosFavoritos()->select('barrios.id')),
            )
            ->orderBy('inicio')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Evento $evento) => EventoResource::make($evento)->resolve());

        return Inertia::render('Eventos/Index', [
            'eventos' => Inertia::scroll($eventos),
            'categorias' => Categoria::activas()->get(['id', 'nombre', 'color', 'icono']),
            'filtros' => [
                'categorias' => array_map('intval', $filtros['categorias'] ?? []),
                'alcance' => $filtros['alcance'] ?? 'todos',
            ],
        ]);
    }

    public function show(Evento $evento): Response
    {
        Gate::authorize('view', $evento);

        return Inertia::render('Eventos/Show', [
            'evento' => EventoResource::make($evento->load(self::RELACIONES))->resolve(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Evento::class);

        return $this->formulario();
    }

    public function store(EventoRequest $request): RedirectResponse
    {
        $evento = new Evento($request->datosEvento());
        $evento->user_id = $request->user()->id;
        $evento->estado = $request->user()->esModerador() ? EstadoEvento::Publicado : EstadoEvento::Pendiente;
        $this->aplicarImagen($request, $evento);
        $evento->save();

        Inertia::flash('exito', $evento->estado === EstadoEvento::Publicado
            ? 'Evento publicado.'
            : 'Lo recibimos: se publica cuando lo apruebe un moderador.');

        return to_route('mis-eventos');
    }

    public function edit(Evento $evento): Response
    {
        Gate::authorize('update', $evento);

        return $this->formulario($evento);
    }

    public function duplicar(Evento $evento): Response
    {
        Gate::authorize('update', $evento);

        return $this->formulario($evento, duplicar: true);
    }

    public function update(EventoRequest $request, Evento $evento): RedirectResponse
    {
        $evento->fill($request->datosEvento());

        // isDirty compara contra el original con casts: un pin idéntico o la misma fecha no disparan reaprobación.
        $cambioImagen = $request->hasFile('imagen') || $request->boolean('quitar_imagen');
        $cambioSensible = $cambioImagen || $evento->isDirty(Evento::CAMPOS_SENSIBLES);

        if (! $request->user()->esModerador()) {
            $vuelveARevision = $evento->estado === EstadoEvento::Rechazado
                || ($evento->estado === EstadoEvento::Publicado && $cambioSensible);

            if ($vuelveARevision) {
                $evento->estado = EstadoEvento::Pendiente;
                $evento->motivo_rechazo = null;
            }
        }

        $this->aplicarImagen($request, $evento);
        $evento->save();

        if ($evento->wasChanged(Evento::CAMPOS_HORARIO)) {
            Notification::send($evento->agendadoPor, new CambioEnEvento($evento, CambioEnEvento::HORARIO));
        }

        Inertia::flash('exito', $evento->estado === EstadoEvento::Pendiente
            ? 'Guardado: el evento queda en revisión.'
            : 'Evento actualizado.');

        return to_route('mis-eventos');
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        Gate::authorize('delete', $evento);

        Notification::send($evento->agendadoPor, new CambioEnEvento($evento, CambioEnEvento::CANCELADO));
        $evento->delete();
        Inertia::flash('exito', 'Evento eliminado.');

        return to_route('mis-eventos');
    }

    public function misEventos(Request $request): Response
    {
        $eventos = $request->user()->esModerador()
            ? Evento::query()
            : Evento::where('user_id', $request->user()->id);

        return Inertia::render('Eventos/MisEventos', [
            'eventos' => EventoResource::collection(
                $eventos->with(self::RELACIONES)->latest('inicio')->limit(100)->get()
            )->resolve(),
        ]);
    }

    private function formulario(?Evento $evento = null, bool $duplicar = false): Response
    {
        return Inertia::render('Eventos/Form', [
            // datetime-local exige 'Y-m-d\TH:i' en hora local (APP_TIMEZONE = Buenos Aires).
            'evento' => $evento ? [
                'id' => $duplicar ? null : $evento->id,
                'titulo' => $evento->titulo,
                'descripcion' => $evento->descripcion,
                'categoria_id' => $evento->categoria_id,
                'direccion' => $evento->direccion,
                'latitud' => $evento->latitud !== null ? (float) $evento->latitud : null,
                'longitud' => $evento->longitud !== null ? (float) $evento->longitud : null,
                'inicio' => $duplicar ? '' : $evento->inicio->format('Y-m-d\TH:i'),
                'fin' => $duplicar ? '' : ($evento->fin?->format('Y-m-d\TH:i') ?? ''),
                'imagen_url' => $duplicar ? null : $evento->imagenUrl(),
                'estado' => $duplicar ? null : $evento->estado->value,
            ] : null,
            'categorias' => Categoria::activas()
                ->when($evento, fn ($query) => $query->orWhere('id', $evento->categoria_id))
                ->get(['id', 'nombre', 'icono']),
        ]);
    }

    private function aplicarImagen(EventoRequest $request, Evento $evento): void
    {
        if ($request->hasFile('imagen') || $request->boolean('quitar_imagen')) {
            if ($evento->imagen_path) {
                Storage::disk('public')->delete($evento->imagen_path);
            }

            $evento->imagen_path = null;
        }

        if ($request->hasFile('imagen')) {
            $evento->imagen_path = $request->file('imagen')->store('eventos', 'public');
        }
    }
}
