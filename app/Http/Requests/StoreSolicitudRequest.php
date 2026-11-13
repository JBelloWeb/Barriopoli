<?php

namespace App\Http\Requests;

use App\Enums\EstadoSolicitud;
use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user->rol === Rol::Vecino
            && ! $user->solicitudes()->where('estado', EstadoSolicitud::Pendiente)->exists();
    }

    public function rules(): array
    {
        return [
            'nombre_emprendimiento' => ['required', 'string', 'max:120'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'enlace' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }
}
