<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Agenda personal (N:M). La baja de eventos es lógica: no dispara el cascade,
// así que la fila sobrevive y el evento cancelado sigue visible en la agenda.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evento_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'evento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_user');
    }
};
