<?php

namespace App\Http\Resources;

use App\Enums\Rol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato único del evento (Inertia y REST /api/v1).
 * En Inertia usar ->resolve(): pasado directo lo envolvería en { data: ... }.
 *
 * @mixin \App\Models\Evento
 */
class EventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // En la API, el usuario solo existe en las rutas con HTTP Basic (mis-eventos y escritura).
        $usuario = $request->user();
        $editable = (bool) $usuario?->can('update', $this->resource);

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'direccion' => $this->direccion,
            'latitud' => (float) $this->latitud,
            'longitud' => (float) $this->longitud,
            'inicio' => $this->inicio->toIso8601String(),
            'fin' => $this->fin?->toIso8601String(),
            'estado' => $this->estado->value,
            // Solo para autor y moderación: el motivo es una conversación interna.
            'motivo_rechazo' => $editable ? $this->motivo_rechazo : null,
            'cancelado' => $this->deleted_at !== null,
            'imagen_url' => $this->imagenUrl(),
            'categoria' => $this->categoria->only(['id', 'nombre', 'color', 'icono']),
            'barrio' => $this->barrio->only(['id', 'nombre', 'slug']),
            'organizador' => $this->autor?->emprendimiento ?? $this->autor?->name,
            // Enlaza con /api/v1/emprendimientos/{id}; null si lo cargó un moderador.
            'organizador_id' => $this->autor?->rol === Rol::Emprendedor ? $this->autor->id : null,
            'editable' => $editable,
        ];
    }
}
