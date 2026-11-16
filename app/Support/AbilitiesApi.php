<?php

namespace App\Support;

use App\Models\User;

/** Qué puede hacer un token según el rol. La policy sigue decidiendo sobre cada evento. */
class AbilitiesApi
{
    public const AGENDA = 'agenda';

    public const EVENTOS_ESCRIBIR = 'eventos:escribir';

    public static function para(User $user): array
    {
        return array_values(array_filter([
            self::AGENDA,
            $user->puedePublicar() ? self::EVENTOS_ESCRIBIR : null,
        ]));
    }
}
