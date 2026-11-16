<?php

it('lista los barrios para el buscador', function () {
    $this->get('/barrios')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Barrios/Index')
        ->has('barrios'));
});

it('devuelve 404 para un barrio inexistente', function () {
    $this->get('/barrios/no-existe')->assertNotFound();
});

// /eventos y /agenda ya son reales (EventoTest, AgendaTest).
it('la agenda pide ingresar', function () {
    $this->get('/agenda')->assertRedirect('/login');
});

it('sirve las siluetas simplificadas de los barrios como GeoJSON cacheable', function () {
    $this->seed(Database\Seeders\BarrioSeeder::class);
    Illuminate\Support\Facades\Cache::forget(App\Http\Controllers\BarrioGeoController::CLAVE_CACHE);

    $respuesta = $this->getJson('/geo/barrios.json')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=86400, public')
        ->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonCount(48, 'features')
        ->assertJsonStructure(['features' => [['id', 'properties' => ['id', 'nombre', 'slug', 'comuna'], 'geometry' => ['type', 'coordinates']]]]);

    // GeoJSON exige lon,lat: CABA está en lon ≈ -58, lat ≈ -34.
    $palermo = collect($respuesta->json('features'))->firstWhere('properties.slug', 'palermo');
    [$lon, $lat] = $palermo['geometry']['coordinates'][0][0][0];
    expect($lon)->toBeBetween(-58.6, -58.3)->and($lat)->toBeBetween(-34.75, -34.5)
        ->and(strlen($respuesta->getContent()))->toBeLessThan(80_000);
});

it('muestra la página de error de marca para un 404 web y JSON para la API', function () {
    $this->get('/barrios/no-existe')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('Errores/Estado')->where('estado', 404));

    $this->getJson('/api/v1/barrios/no-existe')->assertNotFound()->assertJsonStructure(['message']);
});
