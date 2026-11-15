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
