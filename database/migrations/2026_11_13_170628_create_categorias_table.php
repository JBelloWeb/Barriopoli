<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Crea el catálogo y convierte el texto libre eventos.categoria en una FK.
// Usa DB y no modelos: la migración tiene que seguir funcionando aunque el modelo cambie.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('slug', 70)->unique();
            $table->char('color', 7);
            $table->string('icono', 16)->nullable();
            $table->boolean('activa')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->after('barrio_id');
        });

        foreach (DB::table('eventos')->distinct()->pluck('categoria') as $texto) {
            // Un texto vacío daría slug '' → se agrupa en "Otros".
            $nombre = Str::limit(trim((string) $texto), 60, '') ?: 'Otros';
            $slug = Str::slug($nombre) ?: 'otros';

            $id = DB::table('categorias')->where('slug', $slug)->value('id')
                ?? DB::table('categorias')->insertGetId([
                    'nombre' => $nombre,
                    'slug' => $slug,
                    'color' => '#6b7280',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('eventos')->where('categoria', $texto)->update(['categoria_id' => $id]);
        }

        Schema::table('eventos', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable(false)->change();
            $table->foreign('categoria_id')->references('id')->on('categorias')->restrictOnDelete();
            $table->dropColumn('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('categoria')->default('')->after('titulo');
        });

        DB::statement('UPDATE eventos e JOIN categorias c ON c.id = e.categoria_id SET e.categoria = c.nombre');

        Schema::table('eventos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });

        Schema::dropIfExists('categorias');
    }
};
