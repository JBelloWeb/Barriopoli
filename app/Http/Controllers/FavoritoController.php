<?php

namespace App\Http\Controllers;

use App\Models\Barrio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// PUT/DELETE y no toggle: idempotentes, un doble toque con mala señal no invierte el estado.
class FavoritoController extends Controller
{
    public function update(Request $request, Barrio $barrio): RedirectResponse
    {
        $request->user()->barriosFavoritos()->syncWithoutDetaching([$barrio->id]);

        return back();
    }

    public function destroy(Request $request, Barrio $barrio): RedirectResponse
    {
        $user = $request->user();
        $user->barriosFavoritos()->detach($barrio->id);

        // Mi barrio siempre es favorito: si deja de serlo, se desmarca.
        if ($user->barrio_propio_id === $barrio->id) {
            $user->forceFill(['barrio_propio_id' => null])->save();
        }

        return back();
    }
}
