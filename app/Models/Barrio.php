<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barrio extends Model
{
    protected $guarded = [];
    protected $casts = ['geojson' => 'array', 'comuna' => 'integer'];

    public function eventos()
    {
        return $this->hasMany(Evento::class);
    }
}
