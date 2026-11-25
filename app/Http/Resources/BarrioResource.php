<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarrioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'comuna' => $this->comuna,
            'geojson' => $this->when($request->routeIs('api.v1.barrios.show'), fn () => $this->geojson),
        ];
    }
}
