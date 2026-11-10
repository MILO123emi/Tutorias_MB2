<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CitacionResource;
use App\Models\Citacion;
use App\Services\BitacoraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CitacionController extends Controller
{
    public function __construct(private BitacoraService $bitacora) {}

    // GET /api/v1/citaciones  (las citaciones dirigidas al usuario)
    public function index(Request $request): AnonymousResourceCollection
    {
        $citaciones = Citacion::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return CitacionResource::collection($citaciones);
    }

    // POST /api/v1/citaciones/{id}/enterado
    public function enterado(Request $request, int $id): JsonResponse
    {
        $citacion = Citacion::where('user_id', $request->user()->id)->findOrFail($id);

        if ($citacion->enterado_at === null) {
            // 'enterado_at' no es fillable a propósito: se asigna directo.
            $citacion->enterado_at = now();
            $citacion->save();
            $this->bitacora->registrar(
                $request->user(),
                'CITACION_ENTERADA',
                'citaciones',
                $citacion->id,
                "El usuario marcó la citación #{$citacion->id} como enterada.",
            );
        }

        return response()->json(['message' => 'Marcaste la citación como enterada.']);
    }
}
