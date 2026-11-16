<?php

namespace App\Enums;

enum EstadoEvento: string
{
    case Pendiente = 'pendiente';
    case Publicado = 'publicado';
    case Rechazado = 'rechazado';
}
