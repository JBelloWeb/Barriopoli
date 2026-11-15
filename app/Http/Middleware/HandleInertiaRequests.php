<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $user->rol->value,
                    'verificado' => $user->hasVerifiedEmail(),
                ] : null,
                'puede' => [
                    'publicar' => (bool) $user?->puedePublicar(),
                    'moderar' => (bool) $user?->esModerador(),
                ],
            ],
            'status' => fn () => $request->session()->get('status'),
            // Primer nivel y closures: una recarga parcial con only: ['favoritosIds', 'barrioPropioId']
            // evalúa y envía solo estas dos, sin reenviar el GeoJSON del barrio.
            'favoritosIds' => fn () => $user ? $user->barriosFavoritos()->pluck('barrios.id') : [],
            'barrioPropioId' => fn () => $user?->barrio_propio_id,
            // Se evalúan después del controlador: al entrar a la agenda los avisos ya están leídos.
            'agendaIds' => fn () => $user ? $user->eventosAgendados()->pluck('eventos.id') : [],
            'avisosSinLeer' => fn () => $user ? $user->unreadNotifications()->count() : 0,
        ];
    }
}
