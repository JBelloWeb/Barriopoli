<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cara pública de un emprendedor: nunca email ni nombre personal.
 *
 * @mixin \App\Models\User
 */
class EmprendimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->emprendimiento,
            'eventos_proximos' => $this->whenCounted('eventos'),
            'eventos' => EventoResource::collection($this->whenLoaded('eventos')),
        ];
    }
}
