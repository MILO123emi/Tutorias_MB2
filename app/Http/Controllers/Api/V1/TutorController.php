<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TutorResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    // GET /api/v1/tutores?carrera_id=&materia_id=&modalidad=&buscar=&page=
    // Por defecto filtra por la carrera del usuario; carrera_id=todas quita el filtro.
    public function index(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate([
            'carrera_id' => ['nullable', function (string $atributo, mixed $valor, \Closure $falla) {
                if ($valor !== 'todas' && ! ctype_digit((string) $valor)) {
                    $falla('La carrera no es válida.');
                }
            }],
            'materia_id' => ['nullable', 'integer'],
            'asignatura_id' => ['nullable', 'integer'],
            'modalidad' => ['nullable', Rule::in(['PRESENCIAL', 'VIRTUAL'])],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $usuario = $request->user();
        $carrera = $datos['carrera_id'] ?? $usuario->carrera_id;
        $materia = $datos['materia_id'] ?? $datos['asignatura_id'] ?? null;
        $buscar = trim($datos['buscar'] ?? '');

        $tutores = $this->tutoresVisibles()
            ->whereKeyNot($usuario->id)
            ->when($carrera !== null && $carrera !== 'todas', fn (Builder $q) => $q->where('carrera_id', (int) $carrera))
            ->when($materia !== null, fn (Builder $q) => $q->whereHas('materias', fn (Builder $m) => $m->whereKey((int) $materia)))
            ->when(isset($datos['modalidad']), fn (Builder $q) => $q->whereHas('disponibilidad', fn (Builder $d) => $d->where('modalidad', $datos['modalidad'])))
            ->when($buscar !== '', fn (Builder $q) => $q->where('name', 'like', '%'.addcslashes($buscar, '%_\\').'%'))
            ->with(['carrera', 'materias' => fn ($m) => $m->orderBy('nombre'), 'perfilTutor'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return TutorResource::collection($tutores);
    }

    // GET /api/v1/tutores/{id}
    public function show(int $id): TutorResource
    {
        $tutor = $this->tutoresVisibles()
            ->with(['carrera', 'materias' => fn ($m) => $m->orderBy('nombre'), 'perfilTutor'])
            ->findOrFail($id);

        return (new TutorResource($tutor))->conDetalle();
    }

    /** Solo cuentas activas con perfil de tutor aprobado. */
    private function tutoresVisibles(): Builder
    {
        return User::query()
            ->where('activo', true)
            ->whereHas('perfilTutor', fn (Builder $p) => $p->where('estado', 'APROBADO'));
    }
}
