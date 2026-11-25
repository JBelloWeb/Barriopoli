<?php

namespace App\Notifications;

use App\Models\Evento;
use Illuminate\Notifications\Notification;

class CambioEnEvento extends Notification
{
    public const HORARIO = 'horario';

    public const CANCELADO = 'cancelado';

    public function __construct(
        private readonly Evento $evento,
        private readonly string $tipo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'evento_id' => $this->evento->id,
            'titulo' => $this->evento->titulo,
            'tipo' => $this->tipo,
            'inicio' => $this->evento->inicio->toIso8601String(),
            'fin' => $this->evento->fin?->toIso8601String(),
        ];
    }
}
