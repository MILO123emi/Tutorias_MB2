<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsultarBloquesRequest;
use App\Http\Resources\BloqueResource;
use App\Models\User;
use App\Services\DisponibilidadService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BloqueController extends Controller
{
    public function index(ConsultarBloquesRequest $request, int $id, DisponibilidadService $servicio): AnonymousResourceCollection
    {
        $tutor = User::where('activo', true)
            ->whereHas('perfilTutor', fn ($query) => $query->where('estado', 'APROBADO'))
            ->with('perfilTutor')->findOrFail($id);

        return BloqueResource::collection($servicio->bloquesDelDia($tutor, $request->validated('fecha')));
    }
}
