<?php

namespace App\Http\Controllers;

use App\Models\Barrio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MiBarrioController extends Controller
{
    public function update(Request $request, Barrio $barrio): RedirectResponse
    {
        $user = $request->user();

        // Mi barrio siempre es favorito: se agrega en la misma transacción.
        DB::transaction(function () use ($user, $barrio) {
            $user->barriosFavoritos()->syncWithoutDetaching([$barrio->id]);
            $user->forceFill(['barrio_propio_id' => $barrio->id])->save();
        });

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['barrio_propio_id' => null])->save();

        return back();
    }
}
