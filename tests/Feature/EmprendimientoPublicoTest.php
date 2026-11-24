<?php

use App\Enums\EstadoEvento;
use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\BarrioSeeder;
use Database\Seeders\CategoriaSeeder;

function eventoDeEmprendedor(User $autor, EstadoEvento $estado, array $extra = []): Evento
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
    $this->emprendedor = User::factory()->emprendedor('Taller Ñandú')->create(['name' => 'Lucía Real', 'email' => 'lucia@example.com']);
});

it('muestra solo los eventos publicados y vigentes del emprendedor, sin sesión', function () {
    $publicado = eventoDeEmprendedor($this->emprendedor, EstadoEvento::Publicado);
    eventoDeEmprendedor($this->emprendedor, EstadoEvento::Pendiente);
    eventoDeEmprendedor($this->emprendedor, EstadoEvento::Publicado, ['inicio' => now()->subWeek()]);
    eventoDeEmprendedor(User::factory()->emprendedor('Otro')->create(), EstadoEvento::Publicado);

    $this->get("/emprendimientos/{$this->emprendedor->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Emprendimientos/Show')
            ->where('emprendimiento', ['id' => $this->emprendedor->id, 'nombre' => 'Taller Ñandú'])
            ->has('eventos', 1)
            ->where('eventos.0.id', $publicado->id)
            ->where('eventos.0.organizador_id', $this->emprendedor->id));
});

it('no expone el nombre real ni el email', function () {
    $respuesta = $this->get("/emprendimientos/{$this->emprendedor->id}")->assertOk();

    expect($respuesta->getContent())->not->toContain('lucia@example.com')->not->toContain('Lucía Real');
});

it('da 404 para vecinos, moderadores, ex emprendedores e ids inexistentes', function () {
    $exEmprendedor = User::factory()->emprendedor('Ya no')->create();
    $exEmprendedor->forceFill(['rol' => Rol::Vecino])->save();

    foreach ([User::factory()->create(), User::factory()->moderador()->create(), $exEmprendedor] as $usuario) {
        $this->get("/emprendimientos/{$usuario->id}")->assertNotFound();
    }
    $this->get('/emprendimientos/999999')->assertNotFound();
});

it('muestra el estado vacío si no tiene eventos próximos', function () {
    $this->get("/emprendimientos/{$this->emprendedor->id}")
        ->assertInertia(fn ($page) => $page->has('eventos', 0));
});
