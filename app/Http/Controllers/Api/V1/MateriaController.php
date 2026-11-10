<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MateriaResource;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MateriaController extends Controller
{
    // GET /api/v1/materias?carrera_id=   (catálogo, opcionalmente filtrado por carrera)
    public function index(Request $request): AnonymousResourceCollection
    {
        $materias = Materia::query()
            ->when($request->filled('carrera_id'), fn ($query) => $query->where('carrera_id', $request->integer('carrera_id')))
            ->orderBy('nombre')
            ->get();

        return MateriaResource::collection($materias);
    }
}
