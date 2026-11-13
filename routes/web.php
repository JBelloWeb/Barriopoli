<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarrioController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\Moderacion\CategoriaController;
use App\Http\Controllers\Moderacion\PanelController;
use App\Http\Controllers\Moderacion\SolicitudController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SolicitudEmprendedorController;

Route::get('/', fn () => redirect('/barrios'));
Route::get('/barrios', [BarrioController::class, 'index']) ->name('barrios.index');
Route::get('/barrios/{slug}', [BarrioController::class, 'show'])->name('barrios.show');
// Provisorias: las reemplazan PLAN-03 (eventos.index) y PLAN-05 (agenda).
Route::inertia('/eventos', 'Proximamente', ['seccion' => 'Eventos'])->name('eventos.index');
Route::inertia('/agenda', 'Proximamente', ['seccion' => 'Agenda'])->name('agenda');
Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil');

    Route::middleware('verified')->group(function () {
        Route::get('/emprendedor/solicitud', [SolicitudEmprendedorController::class, 'create'])->name('solicitud.create');
        Route::post('/emprendedor/solicitud', [SolicitudEmprendedorController::class, 'store'])->name('solicitud.store');
    });
});

Route::middleware(['auth', 'can:moderar'])->prefix('moderacion')->name('moderacion.')->group(function () {
    Route::get('/', [PanelController::class, 'index'])->name('index');
    Route::post('/solicitudes/{solicitud}/aprobar', [SolicitudController::class, 'aprobar'])->name('solicitudes.aprobar');
    Route::post('/solicitudes/{solicitud}/rechazar', [SolicitudController::class, 'rechazar'])->name('solicitudes.rechazar');

    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
});
