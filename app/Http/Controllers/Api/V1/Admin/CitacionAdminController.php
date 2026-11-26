<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrearCitacionRequest;
use App\Http\Resources\CitacionResource;
use App\Services\CitacionService;
use Illuminate\Http\JsonResponse;

class CitacionAdminController extends Controller
{
    // POST /api/v1/admin/citaciones
    public function store(CrearCitacionRequest $request, CitacionService $servicio): JsonResponse
    {
        $citacion = $servicio->crear($request->user(), $request->validated());

        return (new CitacionResource($citacion))->response()->setStatusCode(201);
    }
}
