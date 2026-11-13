<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
{
    protected $fillable = ['barrio_id', 'categoria_id', 'titulo', 'direccion', 'latitud', 'longitud', 'fecha'];

    public function barrio(): BelongsTo
    {
        return $this->belongsTo(Barrio::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}
