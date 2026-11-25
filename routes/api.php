<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('/barrios', [V1\BarrioController::class, 'index'])->name('barrios.index');
    Route::get('/barrios/{barrio:slug}', [V1\BarrioController::class, 'show'])->name('barrios.show');
    Route::get('/categorias', V1\CategoriaController::class)->name('categorias.index');
    Route::get('/eventos', [V1\EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/{evento}', [V1\EventoController::class, 'show'])->whereNumber('evento')->name('eventos.show');
    Route::get('/emprendimientos', [V1\EmprendimientoController::class, 'index'])->name('emprendimientos.index');
    Route::get('/emprendimientos/{emprendedor}', [V1\EmprendimientoController::class, 'show'])->whereNumber('emprendedor')->name('emprendimientos.show');

    Route::middleware(['throttle:api-basic', 'auth.basic.once'])->group(function () {
        Route::get('/mis-eventos', [V1\EventoController::class, 'misEventos'])->name('mis-eventos');
        Route::post('/eventos', [V1\EventoController::class, 'store'])->name('eventos.store');
        Route::put('/eventos/{evento}', [V1\EventoController::class, 'update'])->whereNumber('evento')->name('eventos.update');
        Route::delete('/eventos/{evento}', [V1\EventoController::class, 'destroy'])->whereNumber('evento')->name('eventos.destroy');
    });
});
