<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Geocodificador
{
    private const URL = 'https://nominatim.openstreetmap.org/search';

    private const VIEWBOX_CABA = '-58.54,-34.52,-58.33,-34.71';

    /** @return array{latitud: float, longitud: float}|null */
    public function buscar(string $direccion): ?array
    {
        $direccion = trim($direccion);
        $clave = 'geo:'.md5(Str::lower($direccion));

        if (Cache::has($clave)) {
            return Cache::get($clave);
        }

        try {
            $respuesta = Http::withUserAgent(config('services.nominatim.user_agent'))
                ->timeout(8)
                ->get(self::URL, [
                    'q' => $direccion.', Ciudad Autónoma de Buenos Aires, Argentina',
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'ar',
                    'viewbox' => self::VIEWBOX_CABA,
                    'bounded' => 1,
                ]);
        } catch (ConnectionException) {
            return null; // no se cachea: puede ser una caída momentánea
        }

        if (! $respuesta->successful()) {
            return null;
        }

        $resultado = $respuesta->json()[0] ?? null;
        $punto = $resultado
            ? ['latitud' => (float) $resultado['lat'], 'longitud' => (float) $resultado['lon']]
            : null;

        Cache::put($clave, $punto, now()->addDays(30));

        return $punto;
    }
}
