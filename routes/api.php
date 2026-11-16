<?php

use App\Http\Controllers\Api\V1;
use App\Support\AbilitiesApi;
use Illuminate\Support\Facades\Route;

/*
| API pública de Barriopoli (prefijo /api por bootstrap/app.php).
| Lectura sin auth; escritura con Bearer token de Sanctum + abilities + policies.
*/
Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::post('/tokens', [V1\TokenController::class, 'store'])->middleware('throttle:api-login')->name('tokens.store');

    Route::get('/barrios', [V1\BarrioController::class, 'index'])->name('barrios.index');
    Route::get('/barrios/{barrio:slug}', [V1\BarrioController::class, 'show'])->name('barrios.show');
    Route::get('/categorias', V1\CategoriaController::class)->name('categorias.index');
    Route::get('/eventos', [V1\EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/{evento}', [V1\EventoController::class, 'show'])->whereNumber('evento')->name('eventos.show');
    Route::get('/emprendimientos', [V1\EmprendimientoController::class, 'index'])->name('emprendimientos.index');
    Route::get('/emprendimientos/{emprendedor}', [V1\EmprendimientoController::class, 'show'])->whereNumber('emprendedor')->name('emprendimientos.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/tokens/actual', [V1\TokenController::class, 'destroy'])->name('tokens.destroy');
        Route::get('/yo', [V1\TokenController::class, 'yo'])->name('yo');

        Route::middleware('abilities:'.AbilitiesApi::EVENTOS_ESCRIBIR)->group(function () {
            Route::get('/mis-eventos', [V1\EventoController::class, 'misEventos'])->name('mis-eventos');
            Route::post('/eventos', [V1\EventoController::class, 'store'])->name('eventos.store');
            Route::put('/eventos/{evento}', [V1\EventoController::class, 'update'])->whereNumber('evento')->name('eventos.update');
            Route::delete('/eventos/{evento}', [V1\EventoController::class, 'destroy'])->whereNumber('evento')->name('eventos.destroy');
        });

        Route::middleware('abilities:'.AbilitiesApi::AGENDA)->group(function () {
            Route::get('/agenda', [V1\AgendaController::class, 'index'])->name('agenda.index');
            Route::put('/agenda/{evento}', [V1\AgendaController::class, 'update'])->whereNumber('evento')->name('agenda.update');
            Route::delete('/agenda/{evento}', [V1\AgendaController::class, 'destroy'])->whereNumber('evento')->name('agenda.destroy');
            Route::get('/avisos', [V1\AvisoController::class, 'index'])->name('avisos.index');
            Route::post('/avisos/leidos', [V1\AvisoController::class, 'marcarLeidos'])->name('avisos.leidos');
        });
    });
});
