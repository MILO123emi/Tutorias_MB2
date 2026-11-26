<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltrarNoRealizadasRequest;
use App\Http\Resources\TutoriaNoRealizadaResource;
use App\Models\TutoriaNoRealizada;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoRealizadaAdminController extends Controller
{
    // GET /api/v1/admin/no-realizadas
    public function index(FiltrarNoRealizadasRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();

        $noRealizadas = TutoriaNoRealizada::query()
            ->with(['tutoria.materia', 'tutoria.tutor', 'tutoria.estudiante', 'reportador', 'revisor'])
            ->when($filtros['tipo'] ?? null, fn (Builder $q, string $tipo) => $q->where('tipo', $tipo))
            ->when($request->filled('revisado'), fn (Builder $q) => $request->boolean('revisado')
                ? $q->whereNotNull('revisado_at')
                : $q->sinRevisar())
            ->whereHas('tutoria', fn (Builder $q) => $q
                ->when($filtros['desde'] ?? null, fn (Builder $q, string $desde) => $q->whereDate('fecha', '>=', $desde))
                ->when($filtros['hasta'] ?? null, fn (Builder $q, string $hasta) => $q->whereDate('fecha', '<=', $hasta))
                ->when($filtros['tutor_id'] ?? null, fn (Builder $q, int $id) => $q->where('tutor_id', $id))
                ->when($filtros['estudiante_id'] ?? null, fn (Builder $q, int $id) => $q->where('estudiante_id', $id))
                ->when($filtros['materia_id'] ?? null, fn (Builder $q, int $id) => $q->where('materia_id', $id)))
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return TutoriaNoRealizadaResource::collection($noRealizadas);
    }
}
