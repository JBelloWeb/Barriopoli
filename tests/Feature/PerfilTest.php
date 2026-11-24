<?php

use App\Enums\EstadoSolicitud;
use App\Enums\Rol;
use App\Enums\TipoSolicitud;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('muestra la página de edición a cualquier usuario con sesión, aun sin verificar', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/perfil/editar')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Perfil/Editar')->where('emprendimiento', null));
});

it('pide sesión para editar el perfil y la contraseña', function () {
    $this->get('/perfil/editar')->assertRedirect('/login');
    $this->put('/user/password', [])->assertRedirect('/login');
    $this->put('/user/profile-information', [])->assertRedirect('/login');
});

it('actualiza nombre y email; con email nuevo pide verificarlo otra vez', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'antes@example.com']);

    $this->actingAs($user)->from('/perfil/editar')
        ->put('/user/profile-information', ['name' => 'Nombre Nuevo', 'email' => 'despues@example.com'])
        ->assertRedirect('/perfil/editar')
        ->assertSessionHas('status', 'profile-information-updated');

    $user->refresh();
    expect($user->name)->toBe('Nombre Nuevo')
        ->and($user->email)->toBe('despues@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->puedePublicar())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('con el mismo email no pierde la verificación', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/profile-information', ['name' => 'Otro', 'email' => $user->email]);

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
});

it('rechaza un email que ya usa otra cuenta', function () {
    $otra = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/profile-information', ['name' => 'X', 'email' => $otra->email])
        ->assertSessionHasErrorsIn('updateProfileInformation', ['email' => 'Ese email ya está registrado.']);
});

it('ignora rol y emprendimiento mandados en el perfil', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/profile-information', [
        'name' => 'X', 'email' => $user->email, 'rol' => 'moderador', 'emprendimiento' => 'Colado',
    ]);

    expect($user->refresh()->rol)->toBe(Rol::Vecino)->and($user->emprendimiento)->toBeNull();
});

it('cambia la contraseña con la actual correcta y la rechaza si no coincide', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/password', [
        'current_password' => 'incorrecta', 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
    ])->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'La contraseña actual no coincide.']);

    $this->actingAs($user)->put('/user/password', [
        'current_password' => 'password', 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
    ])->assertSessionHas('status', 'password-updated');

    expect(Hash::check('nueva1234', $user->refresh()->password))->toBeTrue();
});

it('el emprendedor pide un cambio de nombre y el público no cambia hasta aprobarlo', function () {
    $emprendedor = User::factory()->emprendedor('Feria Vieja')->create();

    $this->actingAs($emprendedor)->from('/perfil/editar')
        ->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Feria Nueva', 'motivo' => 'Nos mudamos'])
        ->assertRedirect('/perfil/editar');

    $solicitud = $emprendedor->solicitudes()->first();
    expect($solicitud->tipo)->toBe(TipoSolicitud::CambioNombre)
        ->and($solicitud->estado)->toBe(EstadoSolicitud::Pendiente)
        ->and($solicitud->descripcion)->toBe('Nos mudamos')
        ->and($emprendedor->refresh()->emprendimiento)->toBe('Feria Vieja');

    $this->get('/perfil/editar')->assertInertia(fn ($page) => $page
        ->where('cambioNombre.estado', 'pendiente')
        ->where('cambioNombre.nombre_emprendimiento', 'Feria Nueva'));
});

it('no deja pedir un cambio de nombre a un vecino, repetir el nombre ni tener dos pendientes', function () {
    $this->actingAs(User::factory()->create())
        ->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'X'])
        ->assertForbidden();

    $emprendedor = User::factory()->emprendedor('Feria')->create();
    $this->actingAs($emprendedor)->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Feria'])
        ->assertSessionHasErrors(['nombre_emprendimiento' => 'Es el mismo nombre que ya tenés.']);

    $this->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Uno']);
    $this->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Dos'])
        ->assertSessionHasErrors(['nombre_emprendimiento' => 'Ya tenés una solicitud en revisión.']);
    expect($emprendedor->solicitudes()->count())->toBe(1);
});

it('aprobar un cambio de nombre cambia el emprendimiento y no el rol; rechazarlo guarda el motivo', function () {
    $moderador = User::factory()->moderador()->create();
    $emprendedor = User::factory()->emprendedor('Feria Vieja')->create();

    $this->actingAs($emprendedor)->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Feria Nueva']);
    $solicitud = $emprendedor->solicitudes()->first();

    $this->actingAs($moderador)->get('/moderacion')->assertInertia(fn ($page) => $page
        ->where('solicitudes.0.tipo', 'cambio_nombre')
        ->where('solicitudes.0.user.emprendimiento', 'Feria Vieja'));

    $this->post("/moderacion/solicitudes/{$solicitud->id}/aprobar")->assertRedirect();
    expect($emprendedor->refresh()->emprendimiento)->toBe('Feria Nueva')
        ->and($emprendedor->rol)->toBe(Rol::Emprendedor);

    $this->actingAs($emprendedor)->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Feria Rara']);
    $otra = $emprendedor->solicitudes()->latest('id')->first();
    $this->actingAs($moderador)->post("/moderacion/solicitudes/{$otra->id}/rechazar", ['motivo' => 'Nombre confuso']);

    expect($emprendedor->refresh()->emprendimiento)->toBe('Feria Nueva');
    $this->actingAs($emprendedor)->get('/perfil/editar')->assertInertia(fn ($page) => $page
        ->where('cambioNombre.estado', 'rechazada')
        ->where('cambioNombre.motivo_rechazo', 'Nombre confuso'));
});

it('no aprueba un cambio de nombre si la persona ya no es emprendedor', function () {
    $moderador = User::factory()->moderador()->create();
    $emprendedor = User::factory()->emprendedor('Feria')->create();
    $this->actingAs($emprendedor)->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Otra']);
    $emprendedor->forceFill(['rol' => Rol::Vecino])->save();

    $this->actingAs($moderador)
        ->post("/moderacion/solicitudes/{$emprendedor->solicitudes()->first()->id}/aprobar")
        ->assertStatus(409);
});

it('el perfil muestra solo el estado de la solicitud de alta', function () {
    $emprendedor = User::factory()->emprendedor('Feria')->create();
    $this->actingAs($emprendedor)->post('/perfil/emprendimiento', ['nombre_emprendimiento' => 'Otra']);

    $this->get('/perfil')->assertInertia(fn ($page) => $page->where('solicitud', null));
});
