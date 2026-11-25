<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AprobarPostulacionRequest;
use App\Http\Requests\RechazarPostulacionRequest;
use App\Http\Resources\PerfilTutorResource;
use App\Models\PerfilTutor;
use App\Services\PostulacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostulacionAdminController extends Controller
{
    // GET /api/v1/admin/postulaciones
    public function index(Request $request, PostulacionService $servicio): JsonResponse
    {
        $coleccion = PerfilTutorResource::collection($servicio->pendientes());
        $coleccion->collection = $coleccion->collection->map(fn (PerfilTutorResource $perfil) => $perfil->conPostulante());

        return $coleccion->response();
    }

    // POST /api/v1/admin/postulaciones/{perfil}/aprobar
    public function aprobar(AprobarPostulacionRequest $request, int $perfil, PostulacionService $servicio): JsonResponse
    {
        $perfil = $servicio->aprobar($request->user(), PerfilTutor::findOrFail($perfil), $request->validated('materias'));

        return (new PerfilTutorResource($perfil)->conPostulante())->response();
    }

    // POST /api/v1/admin/postulaciones/{perfil}/rechazar
    public function rechazar(RechazarPostulacionRequest $request, int $perfil, PostulacionService $servicio): JsonResponse
    {
        $perfil = $servicio->rechazar($request->user(), PerfilTutor::findOrFail($perfil), $request->validated('motivo'));

        return (new PerfilTutorResource($perfil)->conPostulante())->response();
    }
}
