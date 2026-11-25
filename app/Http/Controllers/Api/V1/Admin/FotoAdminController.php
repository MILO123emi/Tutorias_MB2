<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RechazarFotoRequest;
use App\Http\Resources\SolicitudFotoResource;
use App\Models\SolicitudFoto;
use App\Services\PerfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FotoAdminController extends Controller
{
    // GET /api/v1/admin/fotos
    public function index(Request $request, PerfilService $servicio): JsonResponse
    {
        $coleccion = SolicitudFotoResource::collection($servicio->fotosPendientes());
        $coleccion->collection = $coleccion->collection->map(fn (SolicitudFotoResource $foto) => $foto->conUsuario());

        return $coleccion->response();
    }

    // POST /api/v1/admin/fotos/{solicitud}/aprobar
    public function aprobar(Request $request, int $solicitud, PerfilService $servicio): JsonResponse
    {
        $solicitud = $servicio->aprobarFoto($request->user(), SolicitudFoto::findOrFail($solicitud));

        return (new SolicitudFotoResource($solicitud))->conUsuario()->response();
    }

    // POST /api/v1/admin/fotos/{solicitud}/rechazar
    public function rechazar(RechazarFotoRequest $request, int $solicitud, PerfilService $servicio): JsonResponse
    {
        $solicitud = $servicio->rechazarFoto($request->user(), SolicitudFoto::findOrFail($solicitud), $request->validated('motivo'));

        return (new SolicitudFotoResource($solicitud))->conUsuario()->response();
    }
}
