<?php

use App\Enums\EstadoEvento;
use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\BarrioSeeder;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function usuarioConRol(Rol $rol): User
{
    $usuario = User::factory()->create();
    $usuario->forceFill(['rol' => $rol])->save();

    return $usuario;
}

beforeEach(function () {
    // Barrios reales: la detección usa ST_Contains sobre los 48 polígonos.
    $this->seed([BarrioSeeder::class, CategoriaSeeder::class]);
    $this->emprendedor = usuarioConRol(Rol::Emprendedor);
    $this->datos = [
        'titulo' => 'Feria de diseño',
        'categoria_id' => Categoria::first()->id,
        'direccion' => 'Plaza Italia',
        'latitud' => -34.5807,
        'longitud' => -58.4209,
        'inicio' => now()->addWeek()->format('Y-m-d\TH:i'),
    ];
});

function eventoPublicadoDe(User $autor, array $datos): Evento
{
    test()->actingAs($autor)->post('/eventos', $datos);
    $evento = Evento::latest('id')->first();
    $evento->forceFill(['estado' => EstadoEvento::Publicado])->save();

    return $evento;
}

it('crea el evento pendiente y detecta el barrio', function () {
    $this->actingAs($this->emprendedor)->post('/eventos', $this->datos)->assertRedirect('/mis-eventos');

    $evento = Evento::first();
    expect($evento->estado)->toBe(EstadoEvento::Pendiente)
        ->and($evento->user_id)->toBe($this->emprendedor->id)
        ->and($evento->barrio->nombre)->toBe('PALERMO');
});

it('ignora estado y barrio enviados por el cliente', function () {
    $this->actingAs($this->emprendedor)->post('/eventos', [...$this->datos, 'estado' => 'publicado', 'barrio_id' => 1]);

    $evento = Evento::first();
    expect($evento->estado)->toBe(EstadoEvento::Pendiente)
        ->and($evento->barrio->nombre)->toBe('PALERMO');
});

it('el moderador publica directo', function () {
    $this->actingAs(usuarioConRol(Rol::Moderador))->post('/eventos', $this->datos);

    expect(Evento::first()->estado)->toBe(EstadoEvento::Publicado);
});

it('rechaza un punto fuera de CABA', function () {
    $this->actingAs($this->emprendedor)
        ->post('/eventos', [...$this->datos, 'latitud' => -38.0, 'longitud' => -57.5])
        ->assertSessionHasErrors('latitud');

    expect(Evento::count())->toBe(0);
});

it('rechaza un fin anterior al inicio', function () {
    $this->actingAs($this->emprendedor)
        ->post('/eventos', [...$this->datos, 'fin' => now()->addDay()->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('fin');
});

it('un vecino no puede publicar', function () {
    $this->actingAs(User::factory()->create())->post('/eventos', $this->datos)->assertForbidden();
});

it('un invitado es enviado al login', function () {
    $this->post('/eventos', $this->datos)->assertRedirect('/login');
});

it('otro emprendedor no puede editar ni borrar el evento', function () {
    $evento = eventoPublicadoDe($this->emprendedor, $this->datos);
    $otro = usuarioConRol(Rol::Emprendedor);

    $this->actingAs($otro)->put("/eventos/{$evento->id}", [...$this->datos, 'titulo' => 'Hackeado'])->assertForbidden();
    $this->actingAs($otro)->delete("/eventos/{$evento->id}")->assertForbidden();
    expect($evento->fresh()->titulo)->toBe('Feria de diseño');
});

it('cambiar el horario se publica directo, cambiar el título vuelve a revisión', function () {
    $evento = eventoPublicadoDe($this->emprendedor, $this->datos);

    $this->put("/eventos/{$evento->id}", [...$this->datos, 'inicio' => now()->addDays(8)->format('Y-m-d\TH:i')]);
    expect($evento->fresh()->estado)->toBe(EstadoEvento::Publicado);

    $this->put("/eventos/{$evento->id}", [...$this->datos, 'titulo' => 'Otro título']);
    expect($evento->fresh()->estado)->toBe(EstadoEvento::Pendiente);
});

it('editar un rechazado lo reenvía a revisión y limpia el motivo', function () {
    $this->actingAs($this->emprendedor)->post('/eventos', $this->datos);
    $evento = Evento::first();
    $evento->forceFill(['estado' => EstadoEvento::Rechazado, 'motivo_rechazo' => 'Falta info'])->save();

    $this->put("/eventos/{$evento->id}", [...$this->datos, 'descripcion' => 'Ahora sí']);

    expect($evento->fresh())
        ->estado->toBe(EstadoEvento::Pendiente)
        ->motivo_rechazo->toBeNull();
});

it('guarda la imagen en el disco público', function () {
    Storage::fake('public');

    $this->actingAs($this->emprendedor)->post('/eventos', [
        ...$this->datos,
        'imagen' => UploadedFile::fake()->image('feria.jpg', 800, 600),
    ]);

    Storage::disk('public')->assertExists(Evento::first()->imagen_path);
});

it('el borrado es lógico', function () {
    $evento = eventoPublicadoDe($this->emprendedor, $this->datos);

    $this->delete("/eventos/{$evento->id}")->assertRedirect('/mis-eventos');

    expect(Evento::count())->toBe(0)
        ->and(Evento::withTrashed()->find($evento->id))->not->toBeNull();
});

it('no muestra eventos pendientes al público', function () {
    $this->actingAs($this->emprendedor)->post('/eventos', $this->datos);
    auth()->logout();

    $this->get('/eventos/'.Evento::first()->id)->assertForbidden();
});

it('el mapa del barrio y el listado solo muestran publicados', function () {
    eventoPublicadoDe($this->emprendedor, $this->datos);
    $this->post('/eventos', [...$this->datos, 'titulo' => 'Pendiente']);
    auth()->logout();

    $this->get('/barrios/palermo')->assertInertia(fn ($page) => $page
        ->has('eventos', 1)
        ->where('eventos.0.titulo', 'Feria de diseño'));

    $this->get('/eventos')->assertInertia(fn ($page) => $page
        ->component('Eventos/Index')
        ->has('eventos.data', 1));
});

it('el moderador aprueba y rechaza', function () {
    $moderador = usuarioConRol(Rol::Moderador);
    $this->actingAs($this->emprendedor)->post('/eventos', $this->datos);
    $this->post('/eventos', [...$this->datos, 'titulo' => 'Segundo']);
    [$primero, $segundo] = Evento::orderBy('id')->get();

    $this->actingAs($moderador)->post("/moderacion/eventos/{$primero->id}/aprobar")->assertRedirect();
    $this->post("/moderacion/eventos/{$segundo->id}/rechazar", ['motivo' => 'Duplicado'])->assertRedirect();

    expect($primero->fresh())->estado->toBe(EstadoEvento::Publicado)->revisado_por->toBe($moderador->id)
        ->and($segundo->fresh())->estado->toBe(EstadoEvento::Rechazado)->motivo_rechazo->toBe('Duplicado');
});

it('un emprendedor no puede aprobar', function () {
    $this->actingAs($this->emprendedor)->post('/eventos', $this->datos);

    $this->post('/moderacion/eventos/'.Evento::first()->id.'/aprobar')->assertForbidden();
});

it('geocodifica dentro de CABA y devuelve el barrio', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([['lat' => '-34.6037', 'lon' => '-58.3816']])]);

    $this->actingAs($this->emprendedor)
        ->getJson('/geocodificar?direccion='.urlencode('Av. Corrientes 1000'))
        ->assertOk()
        ->assertJson(['encontrado' => true, 'barrio' => 'SAN NICOLAS']);
});

it('detecta el barrio de un punto', function () {
    $this->actingAs($this->emprendedor)
        ->getJson('/geocodificar/barrio?latitud=-34.5807&longitud=-58.4209')
        ->assertJson(['barrio' => 'PALERMO']);
});
