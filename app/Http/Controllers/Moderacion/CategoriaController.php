<?php

namespace App\Http\Controllers\Moderacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function store(CategoriaRequest $request): RedirectResponse
    {
        Categoria::create([...$request->validated(), 'slug' => $request->slug()]);
        Inertia::flash('exito', 'Categoría creada.');

        return back();
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update([...$request->validated(), 'slug' => $request->slug()]);
        Inertia::flash('exito', 'Categoría actualizada.');

        return back();
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {

        if (DB::table('eventos')->where('categoria_id', $categoria->id)->exists()) {
            Inertia::flash('error', 'Tiene eventos asociados: desactivala en lugar de borrarla.');

            return back();
        }

        $categoria->delete();
        Inertia::flash('exito', 'Categoría eliminada.');

        return back();
    }
}
