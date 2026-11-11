<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarTutoresRequest;
use App\Http\Requests\VerBloquesRequest;
use App\Http\Resources\TutorDetalleResource;
use App\Http\Resources\TutorResource;
use App\Services\DisponibilidadService;
use App\Services\TutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Date;

class TutorController extends Controller
{
    public function __construct(
        private TutorService $tutores,
        private DisponibilidadService $disponibilidad,
    ) {}

    // GET /api/v1/tutores?carrera_id=&materia_id=&modalidad=&buscar=&page=   ("Explorar")
    public function index(BuscarTutoresRequest $request): AnonymousResourceCollection
    {
        return TutorResource::collection(
            $this->tutores->buscar($request->user(), $request->validated()),
        );
    }

    // GET /api/v1/tutores/{id}   (perfil del tutor)
    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => new TutorDetalleResource($this->tutores->mostrar($id))]);
    }

    // GET /api/v1/tutores/{id}/bloques?fecha=AAAA-MM-DD
    public function bloques(VerBloquesRequest $request, int $id): JsonResponse
    {
        $tutor = $this->tutores->mostrar($id);
        $fecha = Date::parse($request->validated('fecha'));

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'data' => $this->disponibilidad->bloquesDelDia($tutor, $fecha),
        ]);
    }
}
