<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Barrios favoritos (N:M). La PK compuesta impide duplicados desde la base;
// la FK de barrio_id crea además el índice para la consulta inversa.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barrio_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barrio_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'barrio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barrio_user');
    }
};
