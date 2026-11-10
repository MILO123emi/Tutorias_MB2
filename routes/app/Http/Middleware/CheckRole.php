<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->activo) {
            return response()->json(['message' => 'No autorizado o cuenta suspendida.'], 403);
        }

        if (in_array('TUTOR', $roles)) {
            if ($user->perfilTutor && $user->perfilTutor->estado === 'APROBADO') {
                return $next($request);
            }
        }

        if (in_array($user->rol, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'No tienes permisos para acceder a este recurso.'], 403);
    }
}
