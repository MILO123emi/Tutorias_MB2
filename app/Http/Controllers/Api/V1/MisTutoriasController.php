<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TutoriaResource;
use App\Models\Tutoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MisTutoriasController extends Controller
{
    // GET /api/v1/mis-tutorias?tipo=proximas|historial&page=
    // Próximas: las CONFIRMADA (también las que ya empezaron y esperan "Sí se dio la tutoría").
    // Historial: todas las demás, de la más reciente a la más vieja.
    public function index(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate([
            'tipo' => ['nullable', Rule::in(['proximas', 'historial'])],
        ]);
        $proximas = ($datos['tipo'] ?? 'proximas') === 'proximas';

        $tutorias = Tutoria::query()
            ->where('estudiante_id', $request->user()->id)
            ->when(
                $proximas,
                fn ($q) => $q->where('estado', 'CONFIRMADA')->orderBy('fecha')->orderBy('hora_inicio'),
                fn ($q) => $q->where('estado', '!=', 'CONFIRMADA')->orderByDesc('fecha')->orderByDesc('hora_inicio'),
            )
            ->with(['materia', 'tutor', 'evaluacion'])
            ->paginate(15)
            ->withQueryString();

        return TutoriaResource::collection($tutorias);
    }
}
