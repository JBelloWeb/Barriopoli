<?php

namespace App\Http\Controllers;

use App\Models\Barrio;
use App\Services\Geocodificador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodificacionController extends Controller
{
    public function buscar(Request $request, Geocodificador $geocodificador): JsonResponse
    {
        $datos = $request->validate(['direccion' => ['required', 'string', 'min:4', 'max:255']]);
        $punto = $geocodificador->buscar($datos['direccion']);

        if (! $punto) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado' => true,
            ...$punto,
            'barrio' => $this->barrio($punto['latitud'], $punto['longitud']),
        ]);
    }

    public function detectar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json(['barrio' => $this->barrio((float) $datos['latitud'], (float) $datos['longitud'])]);
    }

    private function barrio(float $latitud, float $longitud): ?string
    {
        return Barrio::queContiene($latitud, $longitud)->value('nombre');
    }
}
