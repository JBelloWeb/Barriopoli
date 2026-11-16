<?php

use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\BarrioSeeder;
use Database\Seeders\CategoriaSeeder;

beforeEach(function () {
    $this->seed(BarrioSeeder::class);
    $this->user = User::factory()->create();
    $this->barrio = Barrio::firstWhere('slug', 'palermo');
});

it('agregar dos veces no duplica', function () {
    $this->actingAs($this->user);
    $this->put("/favoritos/{$this->barrio->slug}")->assertRedirect();
    $this->put("/favoritos/{$this->barrio->slug}")->assertRedirect();

    expect($this->user->barriosFavoritos()->count())->toBe(1);
});

it('quitar un favorito que no existe no falla', function () {
    $this->actingAs($this->user)->delete("/favoritos/{$this->barrio->slug}")->assertRedirect();

    expect($this->user->barriosFavoritos()->count())->toBe(0);
});

it('marcar Mi barrio lo agrega a favoritos', function () {
    $this->actingAs($this->user)->put("/mi-barrio/{$this->barrio->slug}");

    expect($this->user->fresh()->barrio_propio_id)->toBe($this->barrio->id)
        ->and($this->user->barriosFavoritos()->count())->toBe(1);
});

it('quitar el favorito desmarca Mi barrio', function () {
    $this->actingAs($this->user)->put("/mi-barrio/{$this->barrio->slug}");
    $this->delete("/favoritos/{$this->barrio->slug}");

    expect($this->user->fresh()->barrio_propio_id)->toBeNull();
});

it('desmarcar Mi barrio lo deja como favorito', function () {
    $this->actingAs($this->user)->put("/mi-barrio/{$this->barrio->slug}");
    $this->delete('/mi-barrio');

    expect($this->user->fresh()->barrio_propio_id)->toBeNull()
        ->and($this->user->barriosFavoritos()->count())->toBe(1);
});

it('abre Mi barrio al entrar', function () {
    $this->actingAs($this->user)->put("/mi-barrio/{$this->barrio->slug}");

    $this->get('/')->assertRedirect("/barrios/{$this->barrio->slug}");
});

it('sin Mi barrio, la entrada va a la vista de la ciudad', function () {
    $this->get('/')->assertRedirect('/barrios');
});

it('comparte los favoritos con todas las páginas', function () {
    $this->actingAs($this->user)->put("/favoritos/{$this->barrio->slug}");

    $this->get('/barrios')->assertInertia(fn ($page) => $page
        ->where('favoritosIds', [$this->barrio->id])
        ->where('barrioPropioId', null));
});

it('un invitado va al login', function () {
    $this->put("/favoritos/{$this->barrio->slug}")->assertRedirect('/login');
    $this->put("/mi-barrio/{$this->barrio->slug}")->assertRedirect('/login');
});

it('filtra el listado de eventos por mis barrios', function () {
    $this->seed(CategoriaSeeder::class);
    $otro = Barrio::firstWhere('slug', 'recoleta');
    foreach ([$this->barrio, $otro] as $barrio) {
        Evento::forceCreate([
            'barrio_id' => $barrio->id, 'categoria_id' => Categoria::first()->id, 'titulo' => "Evento en {$barrio->nombre}",
            'direccion' => 'Calle 1', 'latitud' => -34.58, 'longitud' => -58.42, 'inicio' => now()->addDay(), 'estado' => 'publicado',
        ]);
    }

    $this->actingAs($this->user)->put("/favoritos/{$this->barrio->slug}");

    $this->get('/eventos?alcance=mis_barrios')->assertInertia(fn ($page) => $page
        ->has('eventos.data', 1)
        ->where('eventos.data.0.barrio.slug', 'palermo')
        ->where('filtros.alcance', 'mis_barrios'));

    $this->get('/eventos')->assertInertia(fn ($page) => $page->has('eventos.data', 2));
});
