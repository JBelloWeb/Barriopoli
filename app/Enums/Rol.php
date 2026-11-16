<?php

namespace App\Enums;

enum Rol: string
{
    case Vecino = 'vecino';
    case Emprendedor = 'emprendedor';
    case Moderador = 'moderador';
}
