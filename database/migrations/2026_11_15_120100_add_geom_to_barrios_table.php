<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Columna espacial para detectar el barrio de un punto con ST_Contains + índice SPATIAL.
// El SPATIAL INDEX exige NOT NULL y SRID fijo: se crea NULL, se llena y recién ahí se restringe.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE barrios ADD COLUMN geom MULTIPOLYGON SRID 4326 NULL AFTER geojson');
        DB::statement("UPDATE barrios SET geom = ST_GeomFromGeoJSON(JSON_EXTRACT(geojson, '$.geometry'))");
        DB::statement('ALTER TABLE barrios MODIFY geom MULTIPOLYGON NOT NULL SRID 4326');
        DB::statement('ALTER TABLE barrios ADD SPATIAL INDEX barrios_geom_spatial (geom)');
    }

    public function down(): void
    {
        Schema::table('barrios', function (Blueprint $table) {
            $table->dropSpatialIndex('barrios_geom_spatial');
            $table->dropColumn('geom');
        });
    }
};
