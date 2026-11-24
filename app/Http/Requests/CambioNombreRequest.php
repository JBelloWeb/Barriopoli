<?php

namespace App\Http\Requests;

use App\Enums\EstadoSolicitud;
use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Pedido de cambio de nombre del emprendimiento: lo aprueba un moderador antes de publicarse. */
class CambioNombreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->rol === Rol::Emprendedor;
    }

    public function rules(): array
    {
        return [
            'nombre_emprendimiento' => ['required', 'string', 'max:120', Rule::notIn([$this->user()->emprendimiento])],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['nombre_emprendimiento.not_in' => 'Es el mismo nombre que ya tenés.'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->user()->solicitudes()->where('estado', EstadoSolicitud::Pendiente)->exists()) {
                    $validator->errors()->add('nombre_emprendimiento', 'Ya tenés una solicitud en revisión.');
                }
            },
        ];
    }
}
