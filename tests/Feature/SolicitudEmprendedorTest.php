<?php

use App\Enums\EstadoSolicitud;
use App\Enums\Rol;
use App\Models\SolicitudEmprendedor;
use App\Models\User;

function moderador(): User
{
    $moderador = User::factory()->create();
    $moderador->forceFill(['rol' => Rol::Moderador])->save();

    return $moderador;
}

function solicitudPendiente(?User $vecino = null): SolicitudEmprendedor
{
    $vecino ??= User::factory()->create();

    return $vecino->solicitudes()->create(['nombre_emprendimiento' => 'Feria', 'descripcion' => 'Ropa usada']);
}

it('permite a un vecino verificado enviar una solicitud', function () {
    $vecino = User::factory()->create();

    $this->actingAs($vecino)
        ->post('/emprendedor/solicitud', [
            'nombre_emprendimiento' => 'Feria del Parque',
            'descripcion' => 'Ropa y vinilos',
            'enlace' => 'https://example.com',
        ])
        ->assertRedirect('/perfil');

    expect($vecino->solicitudes()->first()->estado)->toBe(EstadoSolicitud::Pendiente);
});

it('exige email verificado para solicitar', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/emprendedor/solicitud')
        ->assertRedirect('/email/verify');
});

it('rechaza enlaces que no sean http o https', function () {
    $this->actingAs(User::factory()->create())
        ->post('/emprendedor/solicitud', [
            'nombre_emprendimiento' => 'Feria',
            'descripcion' => 'x',
            'enlace' => 'javascript:alert(1)',
        ])
        ->assertSessionHasErrors('enlace');
});

it('no permite una segunda solicitud mientras hay una pendiente', function () {
    $vecino = User::factory()->create();
    solicitudPendiente($vecino);

    $this->actingAs($vecino)
        ->post('/emprendedor/solicitud', ['nombre_emprendimiento' => 'Otra', 'descripcion' => 'x'])
        ->assertForbidden();
});

it('bloquea el panel de moderación a un vecino', function () {
    $this->actingAs(User::factory()->create())->get('/moderacion')->assertForbidden();
});

it('aprueba una solicitud y cambia el rol', function () {
    $solicitud = solicitudPendiente();

    $this->actingAs(moderador())
        ->post("/moderacion/solicitudes/{$solicitud->id}/aprobar")
        ->assertRedirect();

    $vecino = $solicitud->user->fresh();
    expect($solicitud->fresh()->estado)->toBe(EstadoSolicitud::Aprobada)
        ->and($vecino->rol)->toBe(Rol::Emprendedor)
        ->and($vecino->emprendimiento)->toBe('Feria')
        ->and($vecino->puedePublicar())->toBeTrue();
});

it('no procesa dos veces la misma solicitud', function () {
    $solicitud = solicitudPendiente();
    $moderador = moderador();

    $this->actingAs($moderador)->post("/moderacion/solicitudes/{$solicitud->id}/aprobar");
    $this->actingAs($moderador)->post("/moderacion/solicitudes/{$solicitud->id}/rechazar", ['motivo' => 'tarde'])
        ->assertStatus(409);
});

it('rechaza una solicitud con motivo y mantiene el rol', function () {
    $solicitud = solicitudPendiente();

    $this->actingAs(moderador())
        ->post("/moderacion/solicitudes/{$solicitud->id}/rechazar", ['motivo' => 'Falta información'])
        ->assertRedirect();

    expect($solicitud->fresh()->estado)->toBe(EstadoSolicitud::Rechazada)
        ->and($solicitud->fresh()->motivo_rechazo)->toBe('Falta información')
        ->and($solicitud->user->fresh()->rol)->toBe(Rol::Vecino);
});
