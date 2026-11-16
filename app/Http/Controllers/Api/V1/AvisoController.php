<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Avisos de cambio de horario / cancelación. A diferencia de la web, leerlos no los marca. */
class AvisoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $avisos = $request->user()->unreadNotifications()->latest()->get()->map(fn ($aviso) => [
            'id' => $aviso->id,
            'fecha' => $aviso->created_at->toIso8601String(),
            ...$aviso->data,
        ]);

        return response()->json(['data' => $avisos]);
    }

    public function marcarLeidos(Request $request): Response
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
