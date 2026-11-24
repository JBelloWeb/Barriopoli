<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica una sola request con email y contraseña (HTTP Basic), sin sesión ni cookie.
 * El `auth.basic` de Laravel inicia sesión, y las rutas de la API no tienen sesión.
 */
class AutenticarConBasic
{
    public function handle(Request $request, Closure $next): Response
    {
        return Auth::onceBasic('email') ?? $next($request);
    }
}
