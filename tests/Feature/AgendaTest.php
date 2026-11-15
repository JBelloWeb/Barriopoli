<?php

use App\Enums\EstadoEvento;
use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use App\Notifications\CambioEnEvento;
use App\Services\CalendarioIcs;
use Database\Seeders\BarrioSeeder;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([BarrioSeeder::class, CategoriaSeeder::class]);

    $this->autor = User::factory()->create();
    $this->autor->forceFill(['rol' => Rol::Emprendedor, 'emprendimiento' => 'Taller Ñandú'])->save();
    $this->user = User::factory()->create();

    $this->datos = [
        'titulo' => 'Feria de diseño',
        'categoria_id' => Categoria::first()->id,
        'direccion' => 'Plaza Italia',
        'latitud' => -34.5807,
        'longitud' => -58.4209,
        'inicio' => now()->addWeek()->format('Y-m-d\TH:i'),
    ];

    $this->evento = Evento::forceCreate([
        ...$this->datos,
        'user_id' => $this->autor->id,
        'barrio_id' => Barrio::firstWhere('slug', 'palermo')->id,
        'estado' => EstadoEvento::Publicado,
    ]);
});

it('agendar dos veces no duplica', function () {
    $this->actingAs($this->user);
    $this->put("/agenda/{$this->evento->id}")->assertRedirect();
    $this->put("/agenda/{$this->evento->id}")->assertRedirect();

    expect($this->user->eventosAgendados()->count())->toBe(1);
});

it('no permite agendar un evento pendiente', function () {
    $this->evento->forceFill(['estado' => EstadoEvento::Pendiente])->save();

    $this->actingAs($this->user)->put("/agenda/{$this->evento->id}")->assertForbidden();
});

it('un invitado va al login', function () {
    $this->put("/agenda/{$this->evento->id}")->assertRedirect('/login');
});

it('comparte agendaIds con todas las páginas', function () {
    $this->actingAs($this->user)->put("/agenda/{$this->evento->id}");

    $this->get('/eventos')->assertInertia(fn ($page) => $page->where('agendaIds', [$this->evento->id]));
});

it('avisa al cambiar el horario y no al cambiar otra cosa', function () {
    Notification::fake();
    $this->user->eventosAgendados()->attach($this->evento);

    $this->actingAs($this->autor)->put("/eventos/{$this->evento->id}", [...$this->datos, 'descripcion' => 'Más info']);
    Notification::assertNothingSent();

    $this->put("/eventos/{$this->evento->id}", [...$this->datos, 'inicio' => now()->addDays(20)->format('Y-m-d\TH:i')]);
    Notification::assertSentTo($this->user, CambioEnEvento::class, fn ($aviso) => $aviso->toArray($this->user)['tipo'] === 'horario');
});

it('avisa al cancelar y mantiene el evento en la agenda como cancelado', function () {
    $this->user->eventosAgendados()->attach($this->evento);

    $this->actingAs($this->autor)->delete("/eventos/{$this->evento->id}");

    $this->actingAs($this->user)->get('/agenda')->assertInertia(fn ($page) => $page
        ->component('Agenda/Index')
        ->where('eventos.0.cancelado', true)
        ->where('avisos.0.tipo', 'cancelado')
        ->where('avisosSinLeer', 0));

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('quita de la agenda un evento cancelado', function () {
    $this->user->eventosAgendados()->attach($this->evento);
    $this->evento->delete();

    $this->actingAs($this->user)->delete("/agenda/{$this->evento->id}")->assertRedirect();

    expect($this->user->eventosAgendados()->withTrashed()->count())->toBe(0);
});

it('separa próximos y pasados', function () {
    $pasado = Evento::forceCreate([
        ...$this->datos, 'titulo' => 'Ya fue', 'inicio' => now()->subDays(3),
        'barrio_id' => $this->evento->barrio_id, 'estado' => EstadoEvento::Publicado,
    ]);
    $this->user->eventosAgendados()->attach([$this->evento->id, $pasado->id]);

    $this->actingAs($this->user)->get('/agenda')
        ->assertInertia(fn ($page) => $page->has('eventos', 1)->where('eventos.0.titulo', 'Feria de diseño'));
    $this->get('/agenda?vista=pasados')
        ->assertInertia(fn ($page) => $page->has('eventos', 1)->where('eventos.0.titulo', 'Ya fue')->where('vista', 'pasados'));
});

it('genera un ics válido, plegado y con UTF-8 intacto', function () {
    $this->evento->forceFill([
        'descripcion' => "Feria con artesanías, música; y comida.\nTraé mate — habrá ñoquis de la abuela para todo el barrio de Palermo",
    ])->save();

    $ics = app(CalendarioIcs::class)->generar([$this->evento->load('barrio')]);

    expect($ics)->toStartWith("BEGIN:VCALENDAR\r\n")
        ->toContain("UID:evento-{$this->evento->id}@barriopoli")
        ->toContain('DTSTART:'.$this->evento->inicio->copy()->utc()->format('Ymd\THis\Z'))
        ->toContain('artesanías\, música\; y comida.\n')
        ->toEndWith("END:VCALENDAR\r\n");

    foreach (explode("\r\n", $ics) as $linea) {
        expect(strlen($linea))->toBeLessThanOrEqual(75)
            ->and(mb_check_encoding($linea, 'UTF-8'))->toBeTrue();
    }

    // Desplegar (RFC 5545) devuelve el texto original.
    expect(str_replace("\r\n ", '', $ics))->toContain('habrá ñoquis de la abuela para todo el barrio de Palermo');
});

it('descarga el ics de un evento publicado y no el de uno pendiente', function () {
    $this->get("/eventos/{$this->evento->id}/calendario.ics")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertDownload('feria-de-diseno.ics');

    $this->evento->forceFill(['estado' => EstadoEvento::Pendiente])->save();
    $this->get("/eventos/{$this->evento->id}/calendario.ics")->assertForbidden();
});

it('exporta la agenda completa solo con publicados vigentes', function () {
    $this->user->eventosAgendados()->attach($this->evento);

    $respuesta = $this->actingAs($this->user)->get('/agenda/calendario.ics')->assertOk();

    expect(substr_count($respuesta->getContent(), 'BEGIN:VEVENT'))->toBe(1);
});
