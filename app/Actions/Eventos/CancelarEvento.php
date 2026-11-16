<?php

namespace App\Actions\Eventos;

use App\Models\Evento;
use App\Notifications\CambioEnEvento;
use Illuminate\Support\Facades\Notification;

/** Baja lógica: quien lo agendó lo sigue viendo como "Cancelado". */
class CancelarEvento
{
    public function __invoke(Evento $evento): void
    {
        Notification::send($evento->agendadoPor, new CambioEnEvento($evento, CambioEnEvento::CANCELADO));
        $evento->delete();
    }
}
