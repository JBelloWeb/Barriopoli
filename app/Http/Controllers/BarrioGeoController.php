<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Los 48 barrios simplificados para la vista ciudad del mapa (PLAN-07).
 * 663 KB originales → ~47 KB (5 m de tolerancia). Se calcula una vez y se cachea.
 */
class BarrioGeoController extends Controller
{
    public const CLAVE_CACHE = 'geo.barrios.v1';

    /** ~5 m en grados: invisible a la escala de la ciudad. */
    private const TOLERANCIA = 0.00005;

    public function __invoke(): JsonResponse
    {
        $coleccion = Cache::rememberForever(self::CLAVE_CACHE, function () {
            // ST_Simplify solo opera en SRID cartesiano: se pasa a SRID 0 (ST_AsGeoJSON mantiene lon,lat).
            $filas = DB::select(
                'SELECT id, nombre, slug, comuna, ST_AsGeoJSON(ST_Simplify(ST_SRID(geom, 0), ?), 5) AS geometria FROM barrios ORDER BY nombre',
                [self::TOLERANCIA],
            );

            return [
                'type' => 'FeatureCollection',
                'features' => array_map(fn ($fila) => [
                    'type' => 'Feature',
                    'id' => $fila->id,
                    'properties' => ['id' => $fila->id, 'nombre' => $fila->nombre, 'slug' => $fila->slug, 'comuna' => $fila->comuna],
                    'geometry' => json_decode($fila->geometria, true),
                ], $filas),
            ];
        });

        return response()->json($coleccion)->header('Cache-Control', 'public, max-age=86400');
    }
}
