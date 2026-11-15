<?php

namespace App\Policies;

use App\Enums\EstadoEvento;
use App\Models\Evento;
use App\Models\User;

class EventoPolicy
{
    public function view(?User $user, Evento $evento): bool
    {
        return $evento->estado === EstadoEvento::Publicado
            || ($user && $this->update($user, $evento));
    }

    public function create(User $user): bool
    {
        return $user->puedePublicar();
    }

    public function update(User $user, Evento $evento): bool
    {
        return $user->esModerador()
            || ($user->puedePublicar() && $evento->user_id === $user->id);
    }

    public function delete(User $user, Evento $evento): bool
    {
        return $this->update($user, $evento);
    }
}
