<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PLAN-09: la misma tabla guarda el alta de emprendedor y el cambio de nombre del emprendimiento,
 * así los dos pasan por la misma cola de moderación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_emprendedor', function (Blueprint $table) {
            $table->string('tipo', 20)->default('alta')->after('user_id');
            // Un cambio de nombre no necesita descripción; el alta la sigue exigiendo StoreSolicitudRequest.
            $table->text('descripcion')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_emprendedor', function (Blueprint $table) {
            $table->dropColumn('tipo');
            $table->text('descripcion')->nullable(false)->change();
        });
    }
};
