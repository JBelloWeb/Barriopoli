<?php

namespace App\Models;

use App\Enums\EstadoSolicitud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudEmprendedor extends Model
{
    protected $table = 'solicitudes_emprendedor';

    protected $fillable = ['nombre_emprendimiento', 'descripcion', 'enlace'];

    protected $attributes = ['estado' => 'pendiente'];

    protected $casts = ['estado' => EstadoSolicitud::class, 'revisada_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
