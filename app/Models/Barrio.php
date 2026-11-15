<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Barrio extends Model
{
    protected $guarded = [];

    // Crítico: geom es binaria; serializada hacia Inertia rompe json_encode (UTF-8 inválido).
    protected $hidden = ['geom'];

    protected $casts = ['geojson' => 'array', 'comuna' => 'integer'];

    protected static function booted(): void
    {
        // Mantiene geom sincronizada con geojson (tests y altas sueltas).
        // Ojo: los seeders con WithoutModelEvents no disparan esto; ahí se llama geomDesde() a mano.
        static::saving(function (Barrio $barrio) {
            if ($barrio->isDirty('geojson') && ! $barrio->geom instanceof Expression) {
                $barrio->geom = self::geomDesde($barrio->geojson);
            }
        });
    }

    /** Acepta un Feature o una geometría; un Polygon se envuelve en MultiPolygon (tipo de la columna). */
    public static function geomDesde(array $geojson): Expression
    {
        $geometria = $geojson['geometry'] ?? $geojson;

        if (($geometria['type'] ?? null) === 'Polygon') {
            $geometria = ['type' => 'MultiPolygon', 'coordinates' => [$geometria['coordinates']]];
        }

        return DB::raw('ST_GeomFromGeoJSON('.DB::getPdo()->quote(json_encode($geometria)).')');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    /** MySQL 8 lee SRID 4326 como lat-long: sin 'axis-order=long-lat' no encuentra nada. */
    public function scopeQueContiene(Builder $query, float $latitud, float $longitud): Builder
    {
        return $query->whereRaw(
            "ST_Contains(geom, ST_GeomFromText(?, 4326, 'axis-order=long-lat'))",
            [sprintf('POINT(%.7F %.7F)', $longitud, $latitud)],
        );
    }
}
