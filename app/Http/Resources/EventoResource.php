<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato único del evento (Inertia y futura REST /api/v1).
 * Usar ->resolve(): pasado directo a Inertia lo envolvería en { data: ... }.
 *
 * @mixin \App\Models\Evento
 */
class EventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'motivo_rechazo' => $this->motivo_rechazo,
            'cancelado' => $this->deleted_at !== null,
            'imagen_url' => $this->imagenUrl(),
            'categoria' => $this->categoria->only(['id', 'nombre', 'color', 'icono']),
            'barrio' => $this->barrio->only(['id', 'nombre', 'slug']),
            'organizador' => $this->autor?->emprendimiento ?? $this->autor?->name,
            'editable' => (bool) $request->user()?->can('update', $this->resource),
        ];
    }
}
