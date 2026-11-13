<?php

it('lista los barrios para el buscador', function () {
    $this->get('/barrios')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Barrios/Index')
        ->has('barrios'));
});

it('devuelve 404 para un barrio inexistente', function () {
    $this->get('/barrios/no-existe')->assertNotFound();
});

it('muestra las secciones provisorias de la barra inferior', function (string $url) {
    $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component('Proximamente'));
})->with(['/eventos', '/agenda']);
