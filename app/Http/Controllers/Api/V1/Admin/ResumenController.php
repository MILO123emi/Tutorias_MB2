<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TutoriaResource;
use App\Models\Tutoria;
use App\Services\AdminResumenService;
use Illuminate\Http\JsonResponse;

class ResumenController extends Controller
{
    // GET /api/v1/admin/resumen
    public function show(AdminResumenService $servicio): JsonResponse
    {
        $resumen = $servicio->resumen();

        return response()->json(['data' => [
            'fecha' => $resumen['fecha'],
            'contadores' => $resumen['contadores'],
            'tutorias_hoy' => $resumen['tutorias_hoy']
                ->map(fn (Tutoria $t) => (new TutoriaResource($t))->conContacto()),
        ]]);
    }
}
