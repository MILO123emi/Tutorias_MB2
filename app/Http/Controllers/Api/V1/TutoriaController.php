<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListarTutoriasRequest;
use App\Http\Requests\ReservarTutoriaRequest;
use App\Http\Resources\TutoriaResource;
use App\Services\TutoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TutoriaController extends Controller
{
    public function __construct(private TutoriaService $tutorias) {}

    // POST /api/v1/tutorias   (reservar un bloque)
    public function store(ReservarTutoriaRequest $request): JsonResponse
    {
        $tutoria = $this->tutorias->reservar($request->user(), $request->validated());

        return response()->json([
            'message' => 'Tutoría reservada. Te enviamos la confirmación a tu correo.',
            'data' => new TutoriaResource($tutoria),
        ], 201);
    }

    // GET /api/v1/mis-tutorias?filtro=proximas|historial   (como estudiante)
    public function misTutorias(ListarTutoriasRequest $request): AnonymousResourceCollection
    {
        return TutoriaResource::collection(
            $this->tutorias->misTutorias($request->user(), $request->filtro()),
        );
    }

    // GET /api/v1/mi-agenda?filtro=hoy|proximas|historial   (solo tutor)
    public function agenda(ListarTutoriasRequest $request): AnonymousResourceCollection
    {
        return TutoriaResource::collection(
            $this->tutorias->agenda($request->user(), $request->filtro()),
        );
    }
}
