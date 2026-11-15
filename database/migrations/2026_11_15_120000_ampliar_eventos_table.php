<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PLAN-03: autor, estado de moderación, fin, imagen, soft delete y la FK de barrio_id
// que la migración original no creó (typo `contrained()`).
return new class extends Migration
{
    public function up(): void
    {
        // La FK nunca se creó: limpiar filas huérfanas antes de agregarla.
        DB::table('eventos')->whereNotIn('barrio_id', DB::table('barrios')->select('id'))->delete();

        Schema::table('eventos', function (Blueprint $table) {
            $table->renameColumn('fecha', 'inicio');
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('descripcion')->nullable()->after('titulo');
            $table->dateTime('fin')->nullable()->after('inicio');
            $table->string('imagen_path')->nullable()->after('fin');
            $table->string('estado', 20)->default('pendiente')->after('imagen_path');
            $table->string('motivo_rechazo', 500)->nullable()->after('estado');
            $table->foreignId('revisado_por')->nullable()->after('motivo_rechazo')->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable()->after('revisado_por');
            $table->softDeletes();

            $table->foreign('barrio_id')->references('id')->on('barrios')->cascadeOnDelete();
            $table->index(['estado', 'inicio']);
        });

        // Los eventos ya cargados con coordenadas quedan publicados; los que no, en revisión.
        DB::table('eventos')
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->update(['estado' => 'publicado']);
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropForeign(['barrio_id']);
            $table->dropConstrainedForeignId('revisado_por');
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['estado', 'inicio']);
            $table->dropSoftDeletes();
            $table->dropColumn(['descripcion', 'fin', 'imagen_path', 'estado', 'motivo_rechazo', 'revisado_at']);
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->renameColumn('inicio', 'fecha');
        });
    }
};
