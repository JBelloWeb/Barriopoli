<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\TokenController;
use App\Support\AbilitiesApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Tokens de la API creados desde el Perfil: para que un emprendedor conecte su sitio sin programar un login. */
class ApiTokenController extends Controller
{
    public const MAXIMO_ACTIVOS = 5;

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->puedePublicar(), 403);

        $datos = $request->validate(['nombre' => ['required', 'string', 'max:60']]);

        $user->tokens()->where('expires_at', '<', now())->delete();

        if ($user->tokens()->count() >= self::MAXIMO_ACTIVOS) {
            throw ValidationException::withMessages([
                'nombre' => 'Podés tener hasta '.self::MAXIMO_ACTIVOS.' tokens activos. Revocá uno para crear otro.',
            ]);
        }

        $token = $user->createToken($datos['nombre'], AbilitiesApi::para($user), now()->addDays(TokenController::DIAS_VIGENCIA));

        // Se muestra una sola vez: en la base solo queda el hash.
        Inertia::flash('token', $token->plainTextToken);

        return back();
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();
        Inertia::flash('exito', 'Token revocado.');

        return back();
    }
}
