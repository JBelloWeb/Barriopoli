<?php

namespace App\Models;

use App\Enums\EstadoEvento;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = ['rol' => 'vecino'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }

    public function esModerador(): bool
    {
        return $this->rol === Rol::Moderador;
    }

    public function puedePublicar(): bool
    {
        return $this->hasVerifiedEmail() && in_array($this->rol, [Rol::Emprendedor, Rol::Moderador], true);
    }

    public function scopeEmprendimientosPublicos(Builder $query): Builder
    {
        return $query->where('rol', Rol::Emprendedor)->whereNotNull('emprendimiento');
    }
    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        return $query->when($texto, fn (Builder $q) => $q->where(fn (Builder $w) => $w
            ->where('name', 'like', "%{$texto}%")
            ->orWhere('email', 'like', "%{$texto}%")
            ->orWhere('emprendimiento', 'like', "%{$texto}%")));
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudEmprendedor::class);
    }

    public function barriosFavoritos(): BelongsToMany
    {
        return $this->belongsToMany(Barrio::class)->withTimestamps();
    }

    public function eventosAgendados(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class)->withTimestamps();
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function agendaVisible(string $vista = 'proximos'): BelongsToMany
    {
        $eventos = $this->eventosAgendados()
            ->withTrashed()
            ->where(fn ($query) => $query->whereNotNull('eventos.deleted_at')->orWhere('estado', '!=', EstadoEvento::Rechazado));

        return $vista === 'pasados'
            ? $eventos->whereRaw('COALESCE(eventos.fin, eventos.inicio) < ?', [now()])->orderByDesc('inicio')
            : $eventos->vigentes()->orderBy('inicio');
    }

    public function barrioPropio(): BelongsTo
    {
        return $this->belongsTo(Barrio::class, 'barrio_propio_id');
    }
}
