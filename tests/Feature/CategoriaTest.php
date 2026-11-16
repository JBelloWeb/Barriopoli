<?php

use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;

function moderadorCategorias(): User
{
    $moderador = User::factory()->create();
    $moderador->forceFill(['rol' => Rol::Moderador])->save();

    return $moderador;
}

function barrioDePrueba(): Barrio
{
    return Barrio::forceCreate([
        'nombre' => 'PALERMO',
        'slug' => 'palermo',
        'comuna' => 14,
        'geojson' => ['type' => 'Polygon', 'coordinates' => [[[-58.43, -34.58], [-58.42, -34.58], [-58.42, -34.57], [-58.43, -34.58]]]],
    ]);
}

function categoria(array $datos = []): Categoria
{
    return Categoria::create([
        'nombre' => 'Música', 'slug' => 'musica', 'color' => '#c2410c', 'icono' => '🎵', ...$datos,
    ]);
}

it('crea una categoría con slug', function () {
    $this->actingAs(moderadorCategorias())
        ->post('/moderacion/categorias', ['nombre' => 'Feria Barrial', 'color' => '#15803d', 'icono' => '🛍️', 'activa' => true, 'orden' => 0])
        ->assertRedirect();

    expect(Categoria::firstWhere('slug', 'feria-barrial'))->not->toBeNull();
});

it('rechaza un nombre equivalente a uno existente', function (string $nombre) {
    categoria();

    $this->actingAs(moderadorCategorias())
        ->post('/moderacion/categorias', ['nombre' => $nombre, 'color' => '#000000'])
        ->assertSessionHasErrors('nombre');

    expect(Categoria::count())->toBe(1);
})->with(['Música', 'MÚSICA', 'Musica', 'música!']);

it('valida el formato del color', function () {
    $this->actingAs(moderadorCategorias())
        ->post('/moderacion/categorias', ['nombre' => 'Cine', 'color' => 'red'])
        ->assertSessionHasErrors('color');
});

it('impide a un vecino administrar categorías', function () {
    $this->actingAs(User::factory()->create())
        ->post('/moderacion/categorias', ['nombre' => 'Cine', 'color' => '#1d4ed8'])
        ->assertForbidden();
});

it('no borra una categoría con eventos', function () {
    $categoria = categoria();
    Evento::create([
        'barrio_id' => barrioDePrueba()->id, 'categoria_id' => $categoria->id,
        'titulo' => 'Recital', 'direccion' => 'Calle 1', 'inicio' => now()->addDay(),
    ]);

    $this->actingAs(moderadorCategorias())
        ->delete("/moderacion/categorias/{$categoria->id}")
        ->assertRedirect();

    expect(Categoria::find($categoria->id))->not->toBeNull();
});

it('borra una categoría sin eventos', function () {
    $categoria = categoria();

    $this->actingAs(moderadorCategorias())->delete("/moderacion/categorias/{$categoria->id}")->assertRedirect();

    expect(Categoria::find($categoria->id))->toBeNull();
});

it('envía al mapa la categoría y el color de cada evento', function () {
    $categoria = categoria();
    $barrio = barrioDePrueba();
    Evento::forceCreate([
        'barrio_id' => $barrio->id, 'categoria_id' => $categoria->id, 'titulo' => 'Recital',
        'direccion' => 'Calle 1', 'latitud' => -34.575, 'longitud' => -58.425, 'inicio' => now()->addDay(),
        'estado' => 'publicado',
    ]);
    categoria(['nombre' => 'Vieja', 'slug' => 'vieja', 'activa' => false]);

    $this->get('/barrios/palermo')->assertInertia(fn ($page) => $page
        ->component('Barrios/Show')
        ->where('eventos.0.categoria.id', $categoria->id)
        ->where('eventos.0.categoria.color', '#c2410c')
        ->has('categorias', 1)
        ->where('categorias.0.nombre', 'Música'));
});
