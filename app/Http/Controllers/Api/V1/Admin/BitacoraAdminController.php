<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltrarBitacoraRequest;
use App\Http\Resources\BitacoraResource;
use App\Models\Bitacora;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BitacoraAdminController extends Controller
{
    // GET /api/v1/admin/bitacora
    public function index(FiltrarBitacoraRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();

        $eventos = Bitacora::query()
            ->with(['actor', 'revisor'])
            ->when($filtros['tipo_evento'] ?? null, fn (Builder $q, string $tipo) => $q->where('tipo_evento', $tipo))
            ->when($filtros['actor_id'] ?? null, fn (Builder $q, $id) => $q->where('actor_id', $id))
            ->when($filtros['entidad'] ?? null, fn (Builder $q, string $entidad) => $q->where('entidad', $entidad))
            ->when($filtros['entidad_id'] ?? null, fn (Builder $q, $id) => $q->where('entidad_id', $id))
            ->when($request->filled('requiere_atencion'), fn (Builder $q) => $q->where('requiere_atencion', $request->boolean('requiere_atencion')))
            ->when($request->boolean('pendientes'), fn (Builder $q) => $q->pendientesDeAtencion())
            ->when($filtros['desde'] ?? null, fn (Builder $q, string $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn (Builder $q, string $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return BitacoraResource::collection($eventos);
    }
}
