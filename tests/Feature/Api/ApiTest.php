<?php

use App\Enums\EstadoEvento;
use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use App\Notifications\CambioEnEvento;
use App\Support\AbilitiesApi;
use Database\Seeders\BarrioSeeder;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

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

// --- Tokens ---

it('emite un token con abilities según el rol', function () {
    $this->postJson('/api/v1/tokens', [
        'email' => $this->emprendedor->email, 'password' => 'password', 'dispositivo' => 'postman',
    ])->assertCreated()
        ->assertJsonPath('abilities', [AbilitiesApi::AGENDA, AbilitiesApi::EVENTOS_ESCRIBIR])
        ->assertJsonPath('usuario.emprendimiento', 'Taller Ñandú')
        ->assertJsonStructure(['token', 'expira']);

    $this->postJson('/api/v1/tokens', [
        'email' => $this->vecino->email, 'password' => 'password', 'dispositivo' => 'postman',
    ])->assertCreated()->assertJsonPath('abilities', [AbilitiesApi::AGENDA]);
});

it('rechaza credenciales inválidas y frena la fuerza bruta', function () {
    $credenciales = ['email' => $this->vecino->email, 'password' => 'mala', 'dispositivo' => 'x'];

    foreach (range(1, 5) as $intento) {
        $this->postJson('/api/v1/tokens', $credenciales)->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    $this->postJson('/api/v1/tokens', $credenciales)->assertTooManyRequests();
});

it('reloguearse desde el mismo dispositivo reemplaza el token', function () {
    $credenciales = ['email' => $this->vecino->email, 'password' => 'password', 'dispositivo' => 'celu'];
    $this->postJson('/api/v1/tokens', $credenciales);
    $this->postJson('/api/v1/tokens', $credenciales);

    expect($this->vecino->tokens()->count())->toBe(1);
});

it('revoca el token actual', function () {
    $token = $this->vecino->createToken('celu', AbilitiesApi::para($this->vecino))->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/yo')->assertOk()
        ->assertJsonPath('data.rol', 'vecino')
        ->assertJsonPath('abilities', [AbilitiesApi::AGENDA]);
    $this->withToken($token)->deleteJson('/api/v1/tokens/actual')->assertNoContent();

    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/yo')->assertUnauthorized();
});

it('no acepta un token vencido', function () {
    $token = $this->vecino->createToken('celu', ['agenda'], now()->subMinute())->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/yo')->assertUnauthorized();
});

it('la API no se autentica con la sesión web', function () {
    $this->actingAs($this->emprendedor)->getJson('/api/v1/yo')->assertUnauthorized();
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

it('oculta pendientes ajenos y los muestra a su autor con token', function () {
    $pendiente = apiEvento($this->emprendedor, EstadoEvento::Rechazado, ['motivo_rechazo' => 'Falta la imagen']);

    $this->getJson("/api/v1/eventos/{$pendiente->id}")->assertNotFound();

    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::EVENTOS_ESCRIBIR]);
    $this->getJson("/api/v1/eventos/{$pendiente->id}")->assertOk()
        ->assertJsonPath('data.motivo_rechazo', 'Falta la imagen')
        ->assertJsonPath('data.editable', true);
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
    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::EVENTOS_ESCRIBIR]);

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
    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::EVENTOS_ESCRIBIR]);

    $this->postJson('/api/v1/eventos', [...$this->datos, 'latitud' => -34.4, 'longitud' => -58.9])
        ->assertUnprocessable()
        ->assertJsonPath('errors.latitud.0', 'El punto tiene que estar dentro de un barrio de CABA.');
    $this->postJson('/api/v1/eventos', [...$this->datos, 'inicio' => now()->subDay()->toIso8601String()])
        ->assertUnprocessable()->assertJsonValidationErrors('inicio');
});

it('exige la ability y el rol para escribir', function () {
    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::AGENDA]);
    $this->postJson('/api/v1/eventos', $this->datos)->assertForbidden();

    // Un token viejo con la ability no alcanza si el rol ya no lo permite: decide la policy.
    Sanctum::actingAs($this->vecino, [AbilitiesApi::EVENTOS_ESCRIBIR]);
    $this->postJson('/api/v1/eventos', $this->datos)->assertForbidden();

    $this->app['auth']->forgetGuards();
    $this->withHeader('Authorization', '')->postJson('/api/v1/eventos', $this->datos)->assertUnauthorized();
});

it('editar el título vuelve a revisión y editar el horario avisa a quien lo agendó', function () {
    Notification::fake();
    $evento = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    $this->vecino->eventosAgendados()->attach($evento);
    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::EVENTOS_ESCRIBIR]);

    $nuevoInicio = now()->addWeeks(2)->startOfHour()->toIso8601String();
    $this->putJson("/api/v1/eventos/{$evento->id}", [...$this->datos, 'inicio' => $nuevoInicio])
        ->assertOk()->assertJsonPath('data.estado', 'publicado');
    Notification::assertSentTo($this->vecino, CambioEnEvento::class);

    $this->putJson("/api/v1/eventos/{$evento->id}", [...$this->datos, 'inicio' => $nuevoInicio, 'titulo' => 'Otro título'])
        ->assertOk()->assertJsonPath('data.estado', 'pendiente');
});

it('no deja editar ni borrar eventos ajenos', function () {
    $ajeno = apiEvento(apiUsuario(Rol::Emprendedor), EstadoEvento::Publicado);
    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::EVENTOS_ESCRIBIR]);

    $this->putJson("/api/v1/eventos/{$ajeno->id}", $this->datos)->assertForbidden();
    $this->deleteJson("/api/v1/eventos/{$ajeno->id}")->assertForbidden();
});

it('la baja es lógica y el evento queda cancelado en la agenda', function () {
    $evento = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    $this->vecino->eventosAgendados()->attach($evento);

    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::EVENTOS_ESCRIBIR]);
    $this->deleteJson("/api/v1/eventos/{$evento->id}")->assertNoContent();
    expect(Evento::withTrashed()->find($evento->id)->trashed())->toBeTrue();

    Sanctum::actingAs($this->vecino, [AbilitiesApi::AGENDA]);
    $this->getJson('/api/v1/agenda')->assertOk()->assertJsonPath('data.0.cancelado', true);
});

it('mis eventos lista todos los estados del autor', function () {
    apiEvento($this->emprendedor, EstadoEvento::Pendiente);
    apiEvento($this->emprendedor, EstadoEvento::Rechazado);
    apiEvento(apiUsuario(Rol::Emprendedor), EstadoEvento::Publicado);
    Sanctum::actingAs($this->emprendedor, [AbilitiesApi::EVENTOS_ESCRIBIR]);

    $this->getJson('/api/v1/mis-eventos')->assertOk()->assertJsonCount(2, 'data');
});

// --- Agenda y avisos ---

it('agenda publicados, no pendientes, y quita cancelados', function () {
    $publicado = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    $pendiente = apiEvento($this->emprendedor, EstadoEvento::Pendiente);
    Sanctum::actingAs($this->vecino, [AbilitiesApi::AGENDA]);

    $this->putJson("/api/v1/agenda/{$publicado->id}")->assertNoContent();
    $this->putJson("/api/v1/agenda/{$publicado->id}")->assertNoContent();
    $this->putJson("/api/v1/agenda/{$pendiente->id}")->assertForbidden();
    expect($this->vecino->eventosAgendados()->count())->toBe(1);

    $publicado->delete();
    $this->deleteJson("/api/v1/agenda/{$publicado->id}")->assertNoContent();
    expect($this->vecino->eventosAgendados()->withTrashed()->count())->toBe(0);
});

it('la agenda exige la ability agenda', function () {
    Sanctum::actingAs($this->vecino, []);

    $this->getJson('/api/v1/agenda')->assertForbidden();
});

it('lee avisos sin marcarlos y los marca a pedido', function () {
    $evento = apiEvento($this->emprendedor, EstadoEvento::Publicado);
    $this->vecino->notify(new CambioEnEvento($evento, CambioEnEvento::HORARIO));
    Sanctum::actingAs($this->vecino, [AbilitiesApi::AGENDA]);

    $this->getJson('/api/v1/avisos')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/avisos')->assertJsonCount(1, 'data');

    $this->postJson('/api/v1/avisos/leidos')->assertNoContent();
    $this->getJson('/api/v1/avisos')->assertJsonCount(0, 'data');
});
