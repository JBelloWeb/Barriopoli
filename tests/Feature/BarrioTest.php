<?php

it('lista los barrios para el buscador', function () {
    $this->get('/barrios')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Barrios/Index')
        ->has('barrios'));
});

it('devuelve 404 para un barrio inexistente', function () {
    $this->get('/barrios/no-existe')->assertNotFound();
});

// PLAN-05 reemplaza /agenda; /eventos ya es real (EventoTest).
it('muestra la sección provisoria de agenda', function () {
    $this->get('/agenda')->assertOk()->assertInertia(fn ($page) => $page->component('Proximamente'));
});
