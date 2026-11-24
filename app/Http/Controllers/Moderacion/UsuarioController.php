<?php

namespace App\Http\Controllers\Moderacion;

use App\Actions\Usuarios\CambiarRol;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarRolRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'rol' => ['nullable', Rule::enum(Rol::class)],
        ]);

        $usuarios = User::query()
            ->buscar($filtros['q'] ?? null)
            ->when($filtros['rol'] ?? null, fn ($query, $rol) => $query->where('rol', $rol))
            ->withCount(['eventos', 'barriosFavoritos', 'eventosAgendados'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $usuario) => [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->rol->value,
                'emprendimiento' => $usuario->emprendimiento,
                'verificado' => $usuario->hasVerifiedEmail(),
                'eventos' => $usuario->eventos_count,
                'favoritos' => $usuario->barrios_favoritos_count,
                'agendados' => $usuario->eventos_agendados_count,
            ]);

        return Inertia::render('Moderacion/Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => ['q' => $filtros['q'] ?? '', 'rol' => $filtros['rol'] ?? ''],
        ]);
    }

    public function show(User $usuario): Response
    {
        $usuario->loadCount(['eventos', 'barriosFavoritos', 'eventosAgendados']);

        return Inertia::render('Moderacion/Usuarios/Show', [
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->rol->value,
                'emprendimiento' => $usuario->emprendimiento,
                'verificado' => $usuario->hasVerifiedEmail(),
                'alta' => $usuario->created_at?->toIso8601String(),
                'eventos' => $usuario->eventos_count,
                'favoritos' => $usuario->barrios_favoritos_count,
                'agendados' => $usuario->eventos_agendados_count,
            ],
            'eventos' => $usuario->eventos()->withTrashed()->latest('inicio')->limit(10)
                ->get(['id', 'titulo', 'inicio', 'estado', 'deleted_at'])
                ->map(fn ($evento) => [
                    'id' => $evento->id,
                    'titulo' => $evento->titulo,
                    'inicio' => $evento->inicio->toIso8601String(),
                    'estado' => $evento->estado->value,
                    'cancelado' => $evento->deleted_at !== null,
                ]),
            'solicitudes' => $usuario->solicitudes()->latest('id')->limit(10)
                ->get(['id', 'tipo', 'nombre_emprendimiento', 'estado', 'motivo_rechazo', 'created_at']),
            'barrios' => $usuario->barriosFavoritos()->orderBy('nombre')->pluck('nombre'),
        ]);
    }

    public function cambiarRol(CambiarRolRequest $request, User $usuario, CambiarRol $cambiar): RedirectResponse
    {
        $cambiar($request->user(), $usuario, Rol::from($request->validated('rol')), $request->validated('emprendimiento'));
        Inertia::flash('exito', 'Rol actualizado.');

        return back();
    }
}
