<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('moderar', fn (User $user) => $user->esModerador());
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Nominatim permite 1 req/s: tope por usuario y tope global de la app.
        RateLimiter::for('geocodificar', fn (Request $request) => [
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()),
            Limit::perMinute(50)->by('global'),
        ]);

        // API /api/v1: 60/min por IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        // Escritura con HTTP Basic: corre antes de autenticar, así también frena contraseñas probadas a ciegas.
        RateLimiter::for('api-basic', fn (Request $request) => Limit::perMinute(10)
            ->by(Str::lower((string) $request->getUser()).'|'.$request->ip()));
    }
}
