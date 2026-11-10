<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCuentaActiva
{
    /**
     * Bloquea a las cuentas suspendidas (activo = false) con un 403, aunque el token
     * siga siendo válido.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->activo) {
            abort(403, 'Tu cuenta está suspendida. Comunícate con Bienestar.');
        }

        return $next($request);
    }
}
