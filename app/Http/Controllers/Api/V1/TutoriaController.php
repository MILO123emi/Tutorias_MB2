<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReservarTutoriaRequest;
use App\Http\Resources\TutoriaResource;
use App\Models\Tutoria;
use App\Services\TutoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TutoriaController extends Controller
{
    public function store(ReservarTutoriaRequest $request, TutoriaService $servicio): JsonResponse
    {
        $tutoria = $servicio->reservar($request->user(), $request->validated());

        return (new TutoriaResource($tutoria))->conContacto()->response()->setStatusCode(201);
    }

    public function show(Tutoria $tutoria): TutoriaResource
    {
        Gate::authorize('view', $tutoria);

        return (new TutoriaResource($tutoria->load(['materia', 'tutor', 'estudiante', 'evaluacion'])))->conContacto();
    }
}
