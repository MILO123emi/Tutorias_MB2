<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarreraResource;
use App\Models\Carrera;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CarreraController extends Controller
{
    // GET /api/v1/carreras  (catálogo completo, para los selectores)
    public function index(): AnonymousResourceCollection
    {
        return CarreraResource::collection(Carrera::orderBy('nombre')->get());
    }
}
