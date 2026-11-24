<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalificarTutoriaRequest;
use App\Http\Requests\CancelarTutoriaRequest;
use App\Http\Requests\ReportarInasistenciaRequest;
use App\Http\Requests\ReservarTutoriaRequest;
use App\Http\Resources\EvaluacionResource;
use App\Http\Resources\TutoriaResource;
use App\Models\Tutoria;
use App\Services\EvaluacionService;
use App\Services\TutoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TutoriaController extends Controller
{
    // POST /api/v1/tutorias/{tutoria}/calificar
    public function calificar(CalificarTutoriaRequest $request, Tutoria $tutoria, EvaluacionService $servicio): JsonResponse
    {
        $evaluacion = $servicio->calificar(
            $request->user(),
            $tutoria,
            (int) $request->validated('puntuacion'),
            $request->validated('comentario'),
        );

        return (new EvaluacionResource($evaluacion))->response()->setStatusCode(201);
    }

    public function store(ReservarTutoriaRequest $request, TutoriaService $servicio): JsonResponse
    {
        $tutoria = $servicio->reservar($request->user(), $request->validated());

        return (new TutoriaResource($tutoria))->conContacto()->response()->setStatusCode(201);
    }

    public function cancelar(CancelarTutoriaRequest $request, Tutoria $tutoria, TutoriaService $servicio): TutoriaResource
    {
        return (new TutoriaResource($servicio->cancelar($request->user(), $tutoria, $request->validated('motivo'))))->conContacto();
    }

    public function asistencia(Request $request, Tutoria $tutoria, TutoriaService $servicio): TutoriaResource
    {
        return (new TutoriaResource($servicio->confirmarAsistencia($request->user(), $tutoria)))->conContacto();
    }

    public function inasistencia(ReportarInasistenciaRequest $request, Tutoria $tutoria, TutoriaService $servicio): TutoriaResource
    {
        return (new TutoriaResource($servicio->reportarInasistencia($request->user(), $tutoria, $request->validated('motivo'))))->conContacto();
    }

    public function show(Tutoria $tutoria): TutoriaResource
    {
        Gate::authorize('view', $tutoria);

        return (new TutoriaResource($tutoria->load(['materia', 'tutor', 'estudiante', 'evaluacion'])))->conContacto();
    }
}
