<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitarPostulacionRequest;
use App\Http\Resources\PerfilTutorResource;
use App\Services\PostulacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostulacionController extends Controller
{
    // POST /api/v1/postulaciones
    public function store(SolicitarPostulacionRequest $request, PostulacionService $servicio): JsonResponse
    {
        $perfil = $servicio->postular(
            $request->user(),
            $request->safe()->except('materias'),
            $request->validated('materias'),
        );

        return (new PerfilTutorResource($perfil))->response()->setStatusCode(201);
    }

    // GET /api/v1/postulaciones/mia
    public function mia(Request $request, PostulacionService $servicio): JsonResponse
    {
        $perfil = $servicio->mia($request->user());

        return response()->json(['data' => $perfil ? new PerfilTutorResource($perfil) : null]);
    }
}
