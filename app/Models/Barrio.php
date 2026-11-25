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

    // sucede en la datanbase
    protected $hidden = ['geom'];

    protected $casts = ['geojson' => 'array', 'comuna' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (Barrio $barrio) {
            if ($barrio->isDirty('geojson') && ! $barrio->geom instanceof Expression) {
                $barrio->geom = self::geomDesde($barrio->geojson);
            }
        });
    }

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

    public function scopeQueContiene(Builder $query, float $latitud, float $longitud): Builder
    {
        return $query->whereRaw(
            "ST_Contains(geom, ST_GeomFromText(?, 4326, 'axis-order=long-lat'))",
            [sprintf('POINT(%.7F %.7F)', $longitud, $latitud)],
        );
    }
}
