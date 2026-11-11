<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPerfilTutorRequest;
use App\Http\Requests\SincronizarMateriasRequest;
use App\Http\Requests\SolicitarTutorRequest;
use App\Http\Resources\MateriaResource;
use App\Http\Resources\PerfilTutorResource;
use App\Services\PerfilTutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerfilTutorController extends Controller
{
    public function __construct(private PerfilTutorService $perfiles) {}

    // POST /api/v1/solicitud-tutor   (postularse, con materias)
    public function store(SolicitarTutorRequest $request): JsonResponse
    {
        $perfil = $this->perfiles->solicitar(
            $request->user(),
            $request->safe()->except('materias'),
            $request->validated('materias'),
        );

        return response()->json([
            'message' => 'Postulación enviada. Bienestar la revisará.',
            'data' => new PerfilTutorResource($perfil),
        ], 201);
    }

    // GET /api/v1/perfil-tutor   (estado de mi postulación / perfil)
    public function show(Request $request): JsonResponse
    {
        $perfil = $request->user()->perfilTutor;

        return response()->json(['data' => $perfil ? new PerfilTutorResource($perfil) : null]);
    }

    // PUT /api/v1/perfil-tutor   (bio, lugar y enlace por defecto)
    public function update(ActualizarPerfilTutorRequest $request): JsonResponse
    {
        $perfil = $this->perfiles->actualizar($request->user(), $request->validated());

        return response()->json([
            'message' => 'Perfil de tutor actualizado.',
            'data' => new PerfilTutorResource($perfil),
        ]);
    }

    // GET /api/v1/perfil-tutor/materias
    public function materias(Request $request): JsonResponse
    {
        return response()->json(['data' => MateriaResource::collection($request->user()->materias)]);
    }

    // PUT /api/v1/perfil-tutor/materias
    public function sincronizarMaterias(SincronizarMateriasRequest $request): JsonResponse
    {
        $user = $this->perfiles->sincronizarMaterias($request->user(), $request->validated('materias'));

        return response()->json([
            'message' => 'Materias actualizadas.',
            'data' => MateriaResource::collection($user->materias),
        ]);
    }
}
