<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TutoriaResource;
use App\Models\Tutoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AgendaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate(['tipo' => ['nullable', Rule::in(['hoy', 'proximas', 'historial'])]]);
        $tipo = $datos['tipo'] ?? 'hoy';
        $tutorias = Tutoria::where('tutor_id', $request->user()->id)
            ->when($tipo === 'hoy', fn ($query) => $query->where('fecha', today()->toDateString())->whereNotIn('estado', ['CANCELADA_TUTOR', 'CANCELADA_ESTUDIANTE']))
            ->when($tipo === 'proximas', fn ($query) => $query->where('estado', 'CONFIRMADA'))
            ->when($tipo === 'historial', fn ($query) => $query->where('estado', '!=', 'CONFIRMADA'))
            ->orderBy('fecha', $tipo === 'historial' ? 'desc' : 'asc')
            ->orderBy('hora_inicio', $tipo === 'historial' ? 'desc' : 'asc')
            ->orderBy('id')->with(['materia', 'estudiante', 'evaluacion'])
            ->paginate(15)->withQueryString();

        $recursos = TutoriaResource::collection($tutorias);
        $recursos->collection->each->conContacto();

        return $recursos;
    }
}
