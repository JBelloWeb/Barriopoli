<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\BarrioController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\MiBarrioController;
use App\Http\Controllers\GeocodificacionController;
use App\Http\Controllers\Moderacion\CategoriaController;
use App\Http\Controllers\Moderacion\EventoController as ModeracionEventoController;
use App\Http\Controllers\Moderacion\PanelController;
use App\Http\Controllers\Moderacion\SolicitudController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SolicitudEmprendedorController;

// Con "Mi barrio" marcado, la app abre directo en él.
Route::get('/', function (Request $request) {
    $slug = $request->user()?->barrioPropio()->value('slug');

    return $slug ? to_route('barrios.show', $slug) : to_route('barrios.index');
})->name('inicio');
Route::get('/barrios', [BarrioController::class, 'index']) ->name('barrios.index');
Route::get('/barrios/{slug}', [BarrioController::class, 'show'])->name('barrios.show');
Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
Route::get('/eventos/{evento}', [EventoController::class, 'show'])->whereNumber('evento')->name('eventos.show');
// Pública: la policy 'view' deja descargar solo publicados (o propios).
Route::get('/eventos/{evento}/calendario.ics', [AgendaController::class, 'exportarEvento'])->whereNumber('evento')->name('eventos.ics');

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil');

    // Favoritos y Mi barrio: requieren cuenta, no email verificado (no publican nada).
    Route::put('/favoritos/{barrio:slug}', [FavoritoController::class, 'update'])->name('favoritos.update');
    Route::delete('/favoritos/{barrio:slug}', [FavoritoController::class, 'destroy'])->name('favoritos.destroy');
    Route::put('/mi-barrio/{barrio:slug}', [MiBarrioController::class, 'update'])->name('mi-barrio.update');
    Route::delete('/mi-barrio', [MiBarrioController::class, 'destroy'])->name('mi-barrio.destroy');

    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda');
    Route::get('/agenda/calendario.ics', [AgendaController::class, 'exportarAgenda'])->name('agenda.ics');
    Route::put('/agenda/{evento}', [AgendaController::class, 'update'])->whereNumber('evento')->name('agenda.update');
    Route::delete('/agenda/{evento}', [AgendaController::class, 'destroy'])->whereNumber('evento')->name('agenda.destroy');

    Route::middleware('verified')->group(function () {
        Route::get('/emprendedor/solicitud', [SolicitudEmprendedorController::class, 'create'])->name('solicitud.create');
        Route::post('/emprendedor/solicitud', [SolicitudEmprendedorController::class, 'store'])->name('solicitud.store');

        // Tokens de la API /api/v1 (ApiTokenController exige puedePublicar).
        Route::post('/perfil/tokens', [ApiTokenController::class, 'store'])->middleware('throttle:10,1')->name('perfil.tokens.store');
        Route::delete('/perfil/tokens/{token}', [ApiTokenController::class, 'destroy'])->whereNumber('token')->name('perfil.tokens.destroy');

        // La autorización fina (autor o moderador) la hacen EventoPolicy y EventoRequest.
        Route::get('/mis-eventos', [EventoController::class, 'misEventos'])->name('mis-eventos');
        Route::get('/eventos/crear', [EventoController::class, 'create'])->name('eventos.create');
        Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
        Route::get('/eventos/{evento}/editar', [EventoController::class, 'edit'])->name('eventos.edit');
        Route::get('/eventos/{evento}/duplicar', [EventoController::class, 'duplicar'])->name('eventos.duplicar');
        Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
        Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');

        Route::get('/geocodificar', [GeocodificacionController::class, 'buscar'])->middleware('throttle:geocodificar')->name('geocodificar');
        Route::get('/geocodificar/barrio', [GeocodificacionController::class, 'detectar'])->middleware('throttle:60,1')->name('geocodificar.barrio');
    });
});

Route::middleware(['auth', 'can:moderar'])->prefix('moderacion')->name('moderacion.')->group(function () {
    Route::get('/', [PanelController::class, 'index'])->name('index');
    Route::post('/solicitudes/{solicitud}/aprobar', [SolicitudController::class, 'aprobar'])->name('solicitudes.aprobar');
    Route::post('/solicitudes/{solicitud}/rechazar', [SolicitudController::class, 'rechazar'])->name('solicitudes.rechazar');

    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    Route::post('/eventos/{evento}/aprobar', [ModeracionEventoController::class, 'aprobar'])->name('eventos.aprobar');
    Route::post('/eventos/{evento}/rechazar', [ModeracionEventoController::class, 'rechazar'])->name('eventos.rechazar');
});
