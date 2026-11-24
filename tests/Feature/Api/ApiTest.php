<?php

use App\Enums\EstadoEvento;
use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use App\Notifications\CambioEnEvento;
use Database\Seeders\BarrioSeeder;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Support\Facades\Notification;

function apiUsuario(Rol $rol, array $extra = []): User
{
    $usuario = User::factory()->create();
    $usuario->forceFill(['rol' => $rol, ...$extra])->save();

    return $usuario;
}

function apiEvento(User $autor, EstadoEvento $estado, array $extra = []): Evento
{
    return Evento::forceCreate([
        'titulo' => 'Feria de diseño',
        'categoria_id' => Categoria::first()->id,
        'direccion' => 'Plaza Italia',
        'latitud' => -34.5807,
        'longitud' => -58.4209,
        'inicio' => now()->addWeek(),
        'user_id' => $autor->id,
        'barrio_id' => Barrio::firstWhere('slug', 'palermo')->id,
        'estado' => $estado,
        ...$extra,
    ]);
}

beforeEach(function () {
    $this->seed([BarrioSeeder::class, CategoriaSeeder::class]);
    $this->emprendedor = apiUsuario(Rol::Emprendedor, ['emprendimiento' => 'Taller Ñandú']);
    $this->vecino = apiUsuario(Rol::Vecino);
    $this->datos = [
        'titulo' => 'Feria de diseño',
        'categoria_id' => Categoria::first()->id,
        'direccion' => 'Plaza Italia',
        'latitud' => -34.5807,
        'longitud' => -58.4209,
        // ISO 8601 con offset: lo que manda un cliente externo.
        'inicio' => now()->addWeek()->startOfHour()->toIso8601String(),
    ];
});

// --- Autenticación (HTTP Basic) ---

it('rechaza credenciales inválidas y frena la fuerza bruta', function () {
    foreach (range(1, 10) as $intento) {
        $this->withBasicAuth($this->emprendedor->email, 'mala')->getJson('/api/v1/mis-eventos')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate');
    }

    $this->withBasicAuth($this->emprendedor->email, 'mala')->getJson('/api/v1/mis-eventos')->assertTooManyRequests();
});

it('la API no se autentica con la sesión web', function () {
    $this->actingAs($this->emprendedor);
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/mis-eventos')->assertUnauthorized();
    $this->postJson('/api/v1/eventos', $this->datos)->assertUnauthorized();
});

it('no inicia sesión: cada request trae sus credenciales', function () {
    $this->withBasicAuth($this->emprendedor->email, 'password')->getJson('/api/v1/mis-eventos')
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'));
});

// --- Lectura pública ---

it('lista barrios sin geometría y la devuelve en el detalle', function () {
    $lista = $this->getJson('/api/v1/barrios')->assertOk()->assertJsonCount(48, 'data');
    expect($lista->json('data.0'))->not->toHaveKey('geojson');

    $this->getJson('/api/v1/barrios/palermo')->assertOk()
        ->assertJsonPath('data.slug', 'palermo')
        ->assertJsonStructure(['data' => ['geojson']]);
    $this->getJson('/api/v1/barrios/atlantida')->assertNotFound();
});

it('lista solo categorías activas', function () {
    Categoria::first()->update(['activa' => false]);

    $this->getJson('/api/v1/categorias')->assertOk()
        ->assertJsonCount(Categoria::where('activa', true)->count(), 'data');
});

it('lista solo eventos publicados y vigentes, paginados', function () {
    $publicado = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    apiEvento($this->emprendedor, EstadoEvento::Pendiente);
    apiEvento($this->emprendedor, EstadoEvento::Publicado, ['inicio' => now()->subWeek()]);

    $this->getJson('/api/v1/eventos')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $publicado->id)
        ->assertJsonPath('data.0.organizador_id', $this->emprendedor->id)
        ->assertJsonPath('data.0.motivo_rechazo', null)
        ->assertJsonPath('data.0.editable', false)
        ->assertJsonStructure(['links', 'meta' => ['current_page', 'total']]);
});

it('filtra eventos por barrio, categoría, fechas y emprendimiento', function () {
    $palermo = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    $otro = apiUsuario(Rol::Emprendedor, ['emprendimiento' => 'Otro']);
    $recoleta = apiEvento($otro, EstadoEvento::Publicado, [
        'barrio_id' => Barrio::firstWhere('slug', 'recoleta')->id,
        'categoria_id' => Categoria::skip(1)->first()->id,
        'inicio' => now()->addMonth(),
    ]);

    $ids = fn (string $url) => collect($this->getJson($url)->assertOk()->json('data'))->pluck('id')->all();

    expect($ids('/api/v1/eventos?barrio=recoleta'))->toBe([$recoleta->id])
        ->and($ids('/api/v1/eventos?categorias[]='.$palermo->categoria_id))->toBe([$palermo->id])
        ->and($ids('/api/v1/eventos?hasta='.now()->addWeeks(2)->toDateString()))->toBe([$palermo->id])
        ->and($ids('/api/v1/eventos?desde='.now()->addWeeks(3)->toDateString()))->toBe([$recoleta->id])
        ->and($ids('/api/v1/eventos?emprendimiento='.$otro->id))->toBe([$recoleta->id]);

    $this->getJson('/api/v1/eventos?per_page=51')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    $this->getJson('/api/v1/eventos?barrio=atlantida')->assertUnprocessable();
});

it('oculta los no publicados en el detalle público y los muestra al autor en mis eventos', function () {
    $rechazado = apiEvento($this->emprendedor, EstadoEvento::Rechazado, ['motivo_rechazo' => 'Falta la imagen']);

    $this->getJson("/api/v1/eventos/{$rechazado->id}")->assertNotFound();
    $this->withBasicAuth($this->emprendedor->email, 'password')->getJson("/api/v1/eventos/{$rechazado->id}")->assertNotFound();

    $this->withBasicAuth($this->emprendedor->email, 'password')->getJson('/api/v1/mis-eventos')->assertOk()
        ->assertJsonPath('data.0.id', $rechazado->id)
        ->assertJsonPath('data.0.motivo_rechazo', 'Falta la imagen')
        ->assertJsonPath('data.0.editable', true);
});

it('expone emprendimientos sin datos personales', function () {
    apiEvento($this->emprendedor, EstadoEvento::Publicado);
    apiEvento($this->emprendedor, EstadoEvento::Pendiente);
    apiUsuario(Rol::Emprendedor); // sin nombre público: no aparece

    $this->getJson('/api/v1/emprendimientos')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertExactJson([
            ...$this->getJson('/api/v1/emprendimientos')->json(),
            'data' => [['id' => $this->emprendedor->id, 'nombre' => 'Taller Ñandú', 'eventos_proximos' => 1]],
        ]);

    $this->getJson("/api/v1/emprendimientos/{$this->emprendedor->id}")->assertOk()
        ->assertJsonCount(1, 'data.eventos')
        ->assertJsonMissingPath('data.email');
    $this->getJson("/api/v1/emprendimientos/{$this->vecino->id}")->assertNotFound();
});

// --- Escritura ---

it('crea un evento pendiente con el barrio detectado', function () {
    $this->withBasicAuth($this->emprendedor->email, 'password');

    $respuesta = $this->postJson('/api/v1/eventos', [...$this->datos, 'estado' => 'publicado'])
        ->assertCreated()
        ->assertJsonPath('data.estado', 'pendiente')
        ->assertJsonPath('data.barrio.slug', 'palermo');

    $evento = Evento::find($respuesta->json('data.id'));
    $respuesta->assertHeader('Location', route('api.v1.eventos.show', $evento));
    // El offset -03:00 se respeta: no se corre la hora.
    expect($evento->inicio->equalTo(now()->addWeek()->startOfHour()))->toBeTrue();
});

it('valida el punto y las fechas con los mismos mensajes que la web', function () {
    $this->withBasicAuth($this->emprendedor->email, 'password');

    $this->postJson('/api/v1/eventos', [...$this->datos, 'latitud' => -34.4, 'longitud' => -58.9])
        ->assertUnprocessable()
        ->assertJsonPath('errors.latitud.0', 'El punto tiene que estar dentro de un barrio de CABA.');
    $this->postJson('/api/v1/eventos', [...$this->datos, 'inicio' => now()->subDay()->toIso8601String()])
        ->assertUnprocessable()->assertJsonValidationErrors('inicio');
});

it('exige credenciales y un rol que pueda publicar', function () {
    $this->postJson('/api/v1/eventos', $this->datos)->assertUnauthorized();

    // Sin abilities, decide EventoPolicy: un vecino con credenciales válidas no publica.
    $this->withBasicAuth($this->vecino->email, 'password')->postJson('/api/v1/eventos', $this->datos)->assertForbidden();

    // Tampoco un emprendedor sin email verificado.
    $sinVerificar = apiUsuario(Rol::Emprendedor, ['emprendimiento' => 'Nuevo', 'email_verified_at' => null]);
    $this->withBasicAuth($sinVerificar->email, 'password')->postJson('/api/v1/eventos', $this->datos)->assertForbidden();
});

it('editar el título vuelve a revisión y editar el horario avisa a quien lo agendó', function () {
    Notification::fake();
    $evento = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    $this->vecino->eventosAgendados()->attach($evento);
    $this->withBasicAuth($this->emprendedor->email, 'password');

    $nuevoInicio = now()->addWeeks(2)->startOfHour()->toIso8601String();
    $this->putJson("/api/v1/eventos/{$evento->id}", [...$this->datos, 'inicio' => $nuevoInicio])
        ->assertOk()->assertJsonPath('data.estado', 'publicado');
    Notification::assertSentTo($this->vecino, CambioEnEvento::class);

    $this->putJson("/api/v1/eventos/{$evento->id}", [...$this->datos, 'inicio' => $nuevoInicio, 'titulo' => 'Otro título'])
        ->assertOk()->assertJsonPath('data.estado', 'pendiente');
});

it('no deja editar ni borrar eventos ajenos', function () {
    $ajeno = apiEvento(apiUsuario(Rol::Emprendedor), EstadoEvento::Publicado);
    $this->withBasicAuth($this->emprendedor->email, 'password');

    $this->putJson("/api/v1/eventos/{$ajeno->id}", $this->datos)->assertForbidden();
    $this->deleteJson("/api/v1/eventos/{$ajeno->id}")->assertForbidden();
});

it('la baja es lógica y el evento queda cancelado en la agenda', function () {
    $evento = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    $this->vecino->eventosAgendados()->attach($evento);

    $this->withBasicAuth($this->emprendedor->email, 'password');
    $this->deleteJson("/api/v1/eventos/{$evento->id}")->assertNoContent();
    expect(Evento::withTrashed()->find($evento->id)->trashed())->toBeTrue()
        ->and($this->vecino->agendaVisible()->first()->trashed())->toBeTrue();
});

it('mis eventos lista todos los estados del autor', function () {
    apiEvento($this->emprendedor, EstadoEvento::Pendiente);
    apiEvento($this->emprendedor, EstadoEvento::Rechazado);
    apiEvento(apiUsuario(Rol::Emprendedor), EstadoEvento::Publicado);
    $this->withBasicAuth($this->emprendedor->email, 'password');

    $this->getJson('/api/v1/mis-eventos')->assertOk()->assertJsonCount(2, 'data');
});
