<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioApiResource;
use App\Models\User;
use App\Support\AbilitiesApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public const DIAS_VIGENCIA = 90;

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'dispositivo' => ['required', 'string', 'max:60'],
        ]);

        $user = User::where('email', $datos['email'])->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // Volver a loguearse desde el mismo dispositivo reemplaza su token anterior.
        $user->tokens()->where('name', $datos['dispositivo'])->delete();
        $token = $user->createToken($datos['dispositivo'], AbilitiesApi::para($user), now()->addDays(self::DIAS_VIGENCIA));

        return response()->json([
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
            'expira' => $token->accessToken->expires_at->toIso8601String(),
            'usuario' => UsuarioApiResource::make($user)->resolve(),
        ], 201);
    }

    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function yo(Request $request): JsonResponse
    {
        return UsuarioApiResource::make($request->user())
            ->additional(['abilities' => $request->user()->currentAccessToken()->abilities])
            ->response();
    }
}
