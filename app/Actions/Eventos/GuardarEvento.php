<?php

namespace App\Actions\Eventos;

use App\Enums\EstadoEvento;
use App\Http\Requests\EventoRequest;
use App\Models\Evento;
use App\Notifications\CambioEnEvento;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** Alta y edición de eventos: la comparten la web (Inertia) y la API /api/v1. */
class GuardarEvento
{
    public function crear(EventoRequest $request): Evento
    {
        $evento = new Evento($request->datosEvento());
        $evento->user_id = $request->user()->id;
        $evento->estado = $request->user()->esModerador() ? EstadoEvento::Publicado : EstadoEvento::Pendiente;
        $this->aplicarImagen($request, $evento);
        $evento->save();

        return $evento;
    }

    public function actualizar(EventoRequest $request, Evento $evento): Evento
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

        return $evento;
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
