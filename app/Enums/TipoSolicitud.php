<?php

namespace App\Enums;

enum TipoSolicitud: string
{
    case Alta = 'alta';
    case CambioNombre = 'cambio_nombre';
}
