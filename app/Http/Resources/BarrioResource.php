<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Barrio */
class BarrioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'comuna' => $this->comuna,
            // Solo en el detalle: los 48 polígonos juntos pesan varios MB.
            'geojson' => $this->when($request->routeIs('api.v1.barrios.show'), fn () => $this->geojson),
        ];
    }
}
