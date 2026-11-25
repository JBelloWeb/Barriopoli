<?php

namespace App\Models;

use App\Enums\EstadoEvento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Evento extends Model
{
    use SoftDeletes;

    public const CAMPOS_SENSIBLES = ['titulo', 'descripcion', 'categoria_id', 'direccion', 'latitud', 'longitud'];

    public const CAMPOS_HORARIO = ['inicio', 'fin'];

    protected $fillable = [
        'barrio_id', 'categoria_id', 'titulo', 'descripcion',
        'direccion', 'latitud', 'longitud', 'inicio', 'fin',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'revisado_at' => 'datetime',
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
        'estado' => EstadoEvento::class,
    ];

    public function barrio(): BelongsTo
    {
        return $this->belongsTo(Barrio::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agendadoPor(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('estado', EstadoEvento::Publicado);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereRaw('COALESCE(eventos.fin, eventos.inicio) >= ?', [now()]);
    }

    public function imagenUrl(): ?string
    {
        return $this->imagen_path ? Storage::disk('public')->url($this->imagen_path) : null;
    }
}
