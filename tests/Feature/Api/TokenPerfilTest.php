<?php

use App\Enums\Rol;
use App\Models\User;
use App\Support\AbilitiesApi;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->emprendedor = User::factory()->create();
    $this->emprendedor->forceFill(['rol' => Rol::Emprendedor, 'emprendimiento' => 'Taller'])->save();
});

it('crea un token desde el perfil y lo muestra una sola vez', function () {
    $this->actingAs($this->emprendedor)
        ->post('/perfil/tokens', ['nombre' => 'Mi sitio'])
        ->assertRedirect()
        ->assertInertiaFlash('token');

    $token = $this->emprendedor->tokens()->sole();
    expect($token->name)->toBe('Mi sitio')
        ->and($token->abilities)->toContain(AbilitiesApi::EVENTOS_ESCRIBIR)
        ->and($token->expires_at->isFuture())->toBeTrue();

    $this->get('/perfil')->assertInertia(fn (Assert $page) => $page
        ->has('tokens', 1)
        ->where('tokens.0.nombre', 'Mi sitio')
        ->missing('tokens.0.token'));
});

it('limita la cantidad de tokens activos', function () {
    foreach (range(1, 5) as $i) {
        $this->emprendedor->createToken("t{$i}", ['agenda'], now()->addDay());
    }
    // Uno vencido no cuenta: se limpia al crear.
    $this->emprendedor->createToken('viejo', ['agenda'], now()->subDay());

    $this->actingAs($this->emprendedor)->post('/perfil/tokens', ['nombre' => 'otro'])->assertSessionHasErrors('nombre');
    expect($this->emprendedor->tokens()->count())->toBe(5);
});

it('un vecino no puede crear tokens desde el perfil', function () {
    $this->actingAs(User::factory()->create())->post('/perfil/tokens', ['nombre' => 'x'])->assertForbidden();
});

it('revoca solo tokens propios', function () {
    $ajeno = User::factory()->create()->createToken('ajeno', ['agenda'])->accessToken;
    $propio = $this->emprendedor->createToken('propio', ['agenda'])->accessToken;

    $this->actingAs($this->emprendedor)->delete("/perfil/tokens/{$ajeno->id}")->assertRedirect();
    $this->delete("/perfil/tokens/{$propio->id}")->assertRedirect();

    expect($ajeno->fresh())->not->toBeNull()
        ->and($propio->fresh())->toBeNull();
});
