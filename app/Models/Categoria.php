<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $fillable = ['nombre', 'slug', 'color', 'icono', 'activa', 'orden'];

    protected $casts = ['activa' => 'boolean', 'orden' => 'integer'];

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true)->orderBy('orden')->orderBy('nombre');
    }
}
