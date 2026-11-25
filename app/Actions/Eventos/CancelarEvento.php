<?php

namespace App\Actions\Eventos;

use App\Models\Evento;
use App\Notifications\CambioEnEvento;
use Illuminate\Support\Facades\Notification;

//Si alguien lo agendó, lo ve como cancelado gracias al soft dlete.
class CancelarEvento
{
    public function __invoke(Evento $evento): void
    {
        Notification::send($evento->agendadoPor, new CambioEnEvento($evento, CambioEnEvento::CANCELADO));
        $evento->delete();
    }
}
