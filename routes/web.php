<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarrioController;
use App\Http\Controllers\EventoController;

Route::get('/', fn () => redirect('/barrios'));
Route::get('/barrios', [BarrioController::class, 'index']) ->name('barrios.index');
Route::get('/barrios/{slug}', [BarrioController::class, 'show'])->name('barrios.show');
Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');