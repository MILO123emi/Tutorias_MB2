<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Deja pasar solo a las cuentas con rol ADMIN (RN-24). Responde 403 en el resto.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->esAdmin()) {
            abort(403, 'Necesitas permisos de administrador.');
        }

        return $next($request);
    }
}
