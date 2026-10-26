<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumintae\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
{
    protected $fillable = ['barrio_id', 'titulo', 'categoria','direccion', 'latitud', 'longitud', 'fecha'];

    public function barrio(): BelongsTo{
        return $this->belongsTo(Barrio::class);
    }

    public function edit(Evento $evento)
{
    return Inertia::render('Eventos/Edit', [
        'evento'  => $evento,
        'barrios' => Barrio::select('id', 'nombre')->get(),
    ]);
}
}
