<?php

namespace App\Http\Requests;

use App\Models\Barrio;
use App\Models\Evento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EventoRequest extends FormRequest
{
    public ?Barrio $barrioDetectado = null;

    public function authorize(): bool
    {
        $evento = $this->route('evento');

        return $evento
            ? $this->user()->can('update', $evento)
            : $this->user()->can('create', Evento::class);
    }

    public function rules(): array
    {
        $evento = $this->route('evento');

        return [
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            // También la categoría actual: si se desactivó después, el evento se puede editar sin cambiarla.
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->where(
                fn ($query) => $query->where('activa', true)->orWhere('id', $evento?->categoria_id)
            )],
            'direccion' => ['required', 'string', 'max:255'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'inicio' => $evento ? ['required', 'date'] : ['required', 'date', 'after:now'],
            'fin' => ['nullable', 'date', 'after:inicio'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quitar_imagen' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitud.required' => 'Buscá la dirección o marcá el punto en el mapa.',
            'longitud.required' => 'Buscá la dirección o marcá el punto en el mapa.',
            'fin.after' => 'El fin tiene que ser posterior al inicio.',
            'inicio.after' => 'El evento tiene que ser en el futuro.',
        ];
    }

    /** El barrio lo decide el servidor desde el punto: barrio y coordenadas nunca pueden discrepar. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->barrioDetectado = Barrio::queContiene(
                (float) $this->input('latitud'),
                (float) $this->input('longitud'),
            )->first(['id', 'nombre', 'slug']);

            if (! $this->barrioDetectado) {
                $validator->errors()->add('latitud', 'El punto tiene que estar dentro de un barrio de CABA.');
            }
        }];
    }

    public function datosEvento(): array
    {
        return [
            ...$this->safe()->except(['imagen', 'quitar_imagen']),
            'fin' => $this->validated('fin') ?: null,
            'barrio_id' => $this->barrioDetectado->id,
        ];
    }
}
