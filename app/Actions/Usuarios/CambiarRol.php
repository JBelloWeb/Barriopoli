<?php

namespace App\Actions\Usuarios;

use App\Enums\EstadoSolicitud;
use App\Enums\Rol;
use App\Enums\TipoSolicitud;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class CambiarRol
{
    public function __invoke(User $moderador, User $usuario, Rol $rol, ?string $emprendimiento = null): void
    {
        if ($moderador->is($usuario)) {
            throw ValidationException::withMessages(['rol' => 'No podés cambiar tu propio rol.']);
        }

        if ($rol !== Rol::Vecino && ! $usuario->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['rol' => 'Primero tiene que verificar su email.']);
        }

        DB::transaction(function () use ($moderador, $usuario, $rol, $emprendimiento) {
            
            // Para que exista al menos uno
            if ($usuario->esModerador() && $rol !== Rol::Moderador
                && User::where('rol', Rol::Moderador)->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages(['rol' => 'Tiene que quedar al menos un moderador.']);
            }

            $usuario->forceFill([
                'rol' => $rol,
                'emprendimiento' => $rol === Rol::Emprendedor ? $emprendimiento : $usuario->emprendimiento,
            ])->save();

            // Si tenía un pedido de alta pendiente, queda resuelto por esta decisión.
            if ($rol === Rol::Emprendedor) {
                $usuario->solicitudes()
                    ->where('estado', EstadoSolicitud::Pendiente)
                    ->where('tipo', TipoSolicitud::Alta)
                    ->update([
                        'estado' => EstadoSolicitud::Aprobada,
                        'revisada_por' => $moderador->id,
                        'revisada_at' => now(),
                    ]);
            }
        });
    }
}
