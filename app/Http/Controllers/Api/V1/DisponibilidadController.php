<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarDisponibilidadRequest;
use App\Http\Resources\DisponibilidadResource;
use App\Services\DisponibilidadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DisponibilidadController extends Controller
{
    public function show(Request $request, DisponibilidadService $servicio): AnonymousResourceCollection
    {
        return DisponibilidadResource::collection($servicio->obtener($request->user()));
    }

    public function update(GuardarDisponibilidadRequest $request, DisponibilidadService $servicio): AnonymousResourceCollection
    {
        return DisponibilidadResource::collection($servicio->reemplazar($request->user(), $request->validated('bloques')));
    }
}
