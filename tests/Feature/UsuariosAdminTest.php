<?php

use App\Actions\Usuarios\CambiarRol;
use App\Enums\EstadoEvento;
use App\Enums\EstadoSolicitud;
use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\BarrioSeeder;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Validation\ValidationException;

function eventoDeUsuario(User $autor, array $extra = []): Evento
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
        'estado' => EstadoEvento::Publicado,
        ...$extra,
    ]);
}

beforeEach(function () {
    $this->moderador = User::factory()->moderador()->create(['name' => 'Moderación']);
});

it('solo entra un moderador', function () {
    $this->get('/moderacion/usuarios')->assertRedirect('/login');

    foreach ([User::factory()->create(), User::factory()->emprendedor()->create()] as $usuario) {
        $this->actingAs($usuario)->get('/moderacion/usuarios')->assertForbidden();
        $this->actingAs($usuario)->put("/moderacion/usuarios/{$this->moderador->id}/rol", ['rol' => 'vecino'])->assertForbidden();
    }
});

it('lista usuarios paginados con sus contadores', function () {
    $this->seed([BarrioSeeder::class, CategoriaSeeder::class]);
    $emprendedor = User::factory()->emprendedor('Taller')->create(['name' => 'Ana']);
    eventoDeUsuario($emprendedor);
    eventoDeUsuario($emprendedor);
    $emprendedor->barriosFavoritos()->attach(Barrio::firstWhere('slug', 'palermo'));
    User::factory()->count(15)->create(['name' => 'Zoe']);

    $this->actingAs($this->moderador)->get('/moderacion/usuarios')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Moderacion/Usuarios/Index')
            ->where('usuarios.total', 17)
            ->has('usuarios.data', 15)
            ->where('usuarios.data.0.nombre', 'Ana')
            ->where('usuarios.data.0.eventos', 2)
            ->where('usuarios.data.0.favoritos', 1)
            ->where('usuarios.data.0.agendados', 0)
            ->missing('usuarios.data.0.password'));
});

it('busca por nombre, email o emprendimiento y filtra por rol', function () {
    User::factory()->emprendedor('Feria Jacarandá')->create(['name' => 'Martín', 'email' => 'martin@example.com']);
    User::factory()->create(['name' => 'Sofía', 'email' => 'sofi@example.com']);

    $nombres = fn (string $url) => collect($this->actingAs($this->moderador)->get($url)->viewData('page')['props']['usuarios']['data'])->pluck('nombre')->all();

    expect($nombres('/moderacion/usuarios?q=sofi'))->toBe(['Sofía'])
        ->and($nombres('/moderacion/usuarios?q=jacaranda'))->toBe(['Martín'])
        ->and($nombres('/moderacion/usuarios?q=martin@'))->toBe(['Martín'])
        ->and($nombres('/moderacion/usuarios?rol=emprendedor'))->toBe(['Martín'])
        ->and($nombres('/moderacion/usuarios?rol=moderador'))->toBe(['Moderación']);

    $this->get('/moderacion/usuarios?rol=admin')->assertSessionHasErrors('rol');
});

it('el detalle muestra eventos, incluidos los cancelados, y solicitudes', function () {
    $this->seed([BarrioSeeder::class, CategoriaSeeder::class]);
    $emprendedor = User::factory()->emprendedor('Taller')->create();
    eventoDeUsuario($emprendedor)->delete();
    $emprendedor->solicitudes()->create(['nombre_emprendimiento' => 'Taller', 'descripcion' => 'x']);

    $this->actingAs($this->moderador)->get("/moderacion/usuarios/{$emprendedor->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Moderacion/Usuarios/Show')
            ->where('usuario.rol', 'emprendedor')
            ->where('eventos.0.cancelado', true)
            ->where('solicitudes.0.tipo', 'alta'));
});

it('pasa a un vecino verificado a emprendedor y aprueba su solicitud de alta pendiente', function () {
    $vecino = User::factory()->create();
    $vecino->solicitudes()->create(['nombre_emprendimiento' => 'Feria', 'descripcion' => 'x']);

    $this->actingAs($this->moderador)
        ->put("/moderacion/usuarios/{$vecino->id}/rol", ['rol' => 'emprendedor', 'emprendimiento' => 'Feria del Sur'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $vecino->refresh();
    expect($vecino->rol)->toBe(Rol::Emprendedor)
        ->and($vecino->emprendimiento)->toBe('Feria del Sur')
        ->and($vecino->solicitudes()->first()->estado)->toBe(EstadoSolicitud::Aprobada)
        ->and($vecino->solicitudes()->first()->revisada_por)->toBe($this->moderador->id);
});

it('exige el nombre al pasar a emprendedor y rechaza cuentas sin verificar', function () {
    $vecino = User::factory()->create();
    $this->actingAs($this->moderador)->put("/moderacion/usuarios/{$vecino->id}/rol", ['rol' => 'emprendedor'])
        ->assertSessionHasErrors('emprendimiento');

    $sinVerificar = User::factory()->unverified()->create();
    $this->put("/moderacion/usuarios/{$sinVerificar->id}/rol", ['rol' => 'moderador'])
        ->assertSessionHasErrors(['rol' => 'Primero tiene que verificar su email.']);

    expect($vecino->refresh()->rol)->toBe(Rol::Vecino)->and($sinVerificar->refresh()->rol)->toBe(Rol::Vecino);
});

it('bajar a un emprendedor a vecino deja sus eventos publicados pero ya no los puede editar', function () {
    $this->seed([BarrioSeeder::class, CategoriaSeeder::class]);
    $emprendedor = User::factory()->emprendedor('Taller')->create();
    $evento = eventoDeUsuario($emprendedor);

    $this->actingAs($this->moderador)->put("/moderacion/usuarios/{$emprendedor->id}/rol", ['rol' => 'vecino']);

    expect($emprendedor->refresh()->rol)->toBe(Rol::Vecino)
        ->and($emprendedor->emprendimiento)->toBe('Taller')
        ->and($evento->refresh()->estado)->toBe(EstadoEvento::Publicado);
    $this->actingAs($emprendedor)->get("/eventos/{$evento->id}/editar")->assertForbidden();
});

it('no deja cambiar el propio rol', function () {
    User::factory()->moderador()->create();

    $this->actingAs($this->moderador)->put("/moderacion/usuarios/{$this->moderador->id}/rol", ['rol' => 'vecino'])
        ->assertSessionHasErrors(['rol' => 'No podés cambiar tu propio rol.']);

    expect($this->moderador->refresh()->rol)->toBe(Rol::Moderador);
});

it('baja a otro moderador si queda al menos uno', function () {
    $otro = User::factory()->moderador()->create();

    $this->actingAs($this->moderador)->put("/moderacion/usuarios/{$otro->id}/rol", ['rol' => 'vecino'])
        ->assertSessionHasNoErrors();

    expect($otro->refresh()->rol)->toBe(Rol::Vecino);
});

it('rechaza un rol inválido', function () {
    $vecino = User::factory()->create();

    $this->actingAs($this->moderador)->put("/moderacion/usuarios/{$vecino->id}/rol", ['rol' => 'admin'])
        ->assertSessionHasErrors('rol');
});

it('nunca deja el sistema sin moderadores', function () {
    // Desde la web no se llega (el propio rol está bloqueado); cubre la carrera de dos moderadores
    // bajándose a la vez, que la acción resuelve con lockForUpdate.
    $exModerador = User::factory()->create();

    expect(fn () => app(CambiarRol::class)($exModerador, $this->moderador, Rol::Vecino))
        ->toThrow(ValidationException::class, 'Tiene que quedar al menos un moderador.');
    expect($this->moderador->refresh()->rol)->toBe(Rol::Moderador);
});
