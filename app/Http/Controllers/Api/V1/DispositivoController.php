<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarDispositivoRequest;
use App\Models\Dispositivo;
use Illuminate\Http\JsonResponse;

class DispositivoController extends Controller
{
    // POST /api/v1/dispositivos  (registra o actualiza el token FCM del celular)
    public function store(GuardarDispositivoRequest $request): JsonResponse
    {
        Dispositivo::updateOrCreate(
            ['fcm_token' => $request->validated('fcm_token')],
            ['user_id' => $request->user()->id],
        );

        return response()->json(['message' => 'Dispositivo registrado.'], 201);
    }

    // DELETE /api/v1/dispositivos  (borra el token FCM del celular)
    public function destroy(GuardarDispositivoRequest $request): JsonResponse
    {
        Dispositivo::where('user_id', $request->user()->id)
            ->where('fcm_token', $request->validated('fcm_token'))
            ->delete();

        return response()->json(['message' => 'Dispositivo eliminado.']);
    }
}
