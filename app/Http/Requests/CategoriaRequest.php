<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('moderar');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', Rule::unique('categorias')->ignore($this->route('categoria'))],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icono' => ['nullable', 'string', 'max:16'],
            'activa' => ['boolean'],
            'orden' => ['integer', 'min:0'],
        ];
    }

    /**
     * "Música" y "Musica" son nombres distintos pero comparten slug, que es único:
     * sin este control el segundo alta terminaría en un error 500.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $slug = Str::slug((string) $this->input('nombre'));
                $repetido = DB::table('categorias')
                    ->where('slug', $slug)
                    ->when($this->route('categoria'), fn ($q, $categoria) => $q->where('id', '!=', $categoria->id))
                    ->exists();

                if ($slug === '' || $repetido) {
                    $validator->errors()->add('nombre', 'Ya existe una categoría con un nombre equivalente.');
                }
            },
        ];
    }

    public function slug(): string
    {
        return Str::slug($this->validated('nombre'));
    }
}
