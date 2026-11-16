<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UsuarioApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol->value,
            'emprendimiento' => $this->emprendimiento,
            'email_verificado' => $this->hasVerifiedEmail(),
            'barrio_propio_id' => $this->barrio_propio_id,
        ];
    }
}
