<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificacionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificacionController extends Controller
{
    // GET /api/v1/notificaciones
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificacionResource::collection(
            $request->user()->notifications()->paginate(15),
        );
    }

    // GET /api/v1/notificaciones/no-leidas  (contador para la campanita)
    public function noLeidas(Request $request): JsonResponse
    {
        return response()->json(['no_leidas' => $request->user()->unreadNotifications()->count()]);
    }

    // POST /api/v1/notificaciones/{id}/leida
    public function leida(Request $request, string $id): JsonResponse
    {
        $notificacion = $request->user()->notifications()->findOrFail($id);
        $notificacion->markAsRead();

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    // POST /api/v1/notificaciones/leer-todas
    public function leerTodas(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Todas tus notificaciones quedaron leídas.']);
    }
}
