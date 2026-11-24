<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** La ruta ya exige can:moderar; acá solo se valida el pedido. */
class CambiarRolRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rol' => ['required', Rule::enum(Rol::class)],
            'emprendimiento' => ['nullable', 'required_if:rol,emprendedor', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return ['emprendimiento.required_if' => 'Para ser emprendedor necesita el nombre del emprendimiento.'];
    }
}
