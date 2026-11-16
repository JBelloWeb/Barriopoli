<?php

use App\Enums\Rol;
use App\Models\User;

it('muestra las pantallas de autenticación', function (string $url, string $componente) {
    $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component($componente));
})->with([
    ['/login', 'Auth/Login'],
    ['/register', 'Auth/Register'],
    ['/forgot-password', 'Auth/ForgotPassword'],
]);

it('registra un usuario como vecino aunque envíe otro rol', function () {
    $this->post('/register', [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'clave1234',
        'password_confirmation' => 'clave1234',
        'rol' => 'moderador',
    ])->assertRedirect();

    $ana = User::firstWhere('email', 'ana@example.com');
    expect($ana->rol)->toBe(Rol::Vecino)
        ->and($ana->hasVerifiedEmail())->toBeFalse();
    $this->assertAuthenticatedAs($ana);
});

it('exige letras y números en la contraseña', function () {
    $this->post('/register', [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});

it('inicia sesión y comparte el usuario con el frontend', function () {
    $user = User::factory()->create(['password' => 'clave1234']);

    $this->post('/login', ['email' => $user->email, 'password' => 'clave1234'])->assertRedirect('/');

    $this->get('/perfil')->assertInertia(fn ($page) => $page
        ->component('Perfil/Show')
        ->where('auth.user.email', $user->email)
        ->where('auth.user.rol', 'vecino')
        ->where('auth.puede.moderar', false));
});

it('bloquea el login tras cinco intentos fallidos', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $intento) {
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertStatus(429);
});

it('manda al login a quien entra al perfil sin sesión', function () {
    $this->get('/perfil')->assertRedirect('/login');
});

it('no cierra sesión con GET /logout: con sesión lleva al perfil, sin sesión al mapa', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/logout')->assertRedirect('/perfil');
    $this->assertAuthenticatedAs($user);

    auth()->logout();
    $this->get('/logout')->assertRedirect('/barrios');
});

it('cierra sesión con POST /logout', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect();
    $this->assertGuest();
});
