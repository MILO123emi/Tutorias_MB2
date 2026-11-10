<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tutoria;
use App\Models\Disponibilidad;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TutoriaController extends Controller
{
    public function indexTutores(Request $request)
    {
        $user = $request->user();
        $query = User::whereHas('perfilTutor', fn($q) => $q->where('estado', 'APROBADO'))
            ->with(['carrera', 'perfilTutor.materias']);

        if ($request->filled('carrera_id')) {
            $query->where('carrera_id', $request->carrera_id);
        } elseif (!$request->boolean('quitar_filtro_carrera') && $user->carrera_id) {
            $query->where('carrera_id', $user->carrera_id);
        }

        if ($request->filled('materia_id')) {
            $materiaId = $request->materia_id;
            $query->whereHas('perfilTutor.materias', fn($q) => $q->where('materias.id', $materiaId));
        }

        if ($request->filled('buscar')) {
            $query->where('name', 'like', '%' . $request->buscar . '%');
        }

        $tutores = $query->paginate(10);
        $tutores->getCollection()->transform(fn($tutor) => [
            'id' => $tutor->id,
            'name' => $tutor->name,
            'carrera' => $tutor->carrera?->nombre,
            'foto_url' => $tutor->foto_url,
            'bio' => $tutor->perfilTutor?->bio,
            'promedio_calificacion' => $tutor->perfilTutor?->promedio_calificacion,
            'total_calificaciones' => $tutor->perfilTutor?->total_calificaciones,
            'materias' => $tutor->perfilTutor?->materias->pluck('nombre'),
        ]);

        return response()->json($tutores);
    }

    public function mostrarTutorBloques(Request $request, $id)
    {
        $request->validate(['fecha' => 'required|date|after_or_equal:today']);
        $tutor = User::whereHas('perfilTutor', fn($q) => $q->where('estado', 'APROBADO'))
            ->with(['carrera', 'perfilTutor.materias'])->findOrFail($id);

        $fecha = Carbon::parse($request->fecha);
        if ($fecha->isSunday()) {
            return response()->json(['message' => 'No hay tutorías los días domingo.'], 422);
        }

        $disponibilidades = Disponibilidad::where('tutor_id', $tutor->id)
            ->where('dia_semana', $fecha->dayOfWeekIso)->get();

        $ocupadas = Tutoria::where('tutor_id', $tutor->id)
            ->where('fecha', $fecha->toDateString())
            ->where('estado', 'CONFIRMADA')
            ->pluck('hora_inicio')->toArray();

        $bloques = $disponibilidades->map(fn($d) => [
            'hora_inicio' => $d->hora_inicio,
            'hora_fin' => $d->hora_inicio + 1,
            'estado' => in_array($d->hora_inicio, $ocupadas) ? 'OCUPADO' : 'LIBRE',
            'modalidad' => $d->modalidad,
            'lugar_o_enlace' => $d->lugar_o_enlace ?? ($d->modalidad === 'PRESENCIAL' ? $tutor->perfilTutor->lugar_presencial_default : $tutor->perfilTutor->enlace_virtual_default),
        ]);

        return response()->json(['tutor' => $tutor, 'fecha' => $fecha->toDateString(), 'bloques' => $bloques]);
    }

    public function reservar(Request $request)
    {
        $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'materia_id' => 'required|exists:materias,id',
            'tema' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|integer|between:7,20',
        ]);

        $estudiante = $request->user();
        if ($estudiante->id == $request->tutor_id) {
            return response()->json(['message' => 'No puedes reservar contigo mismo.'], 422);
        }

        $fecha = Carbon::parse($request->fecha);
        if ($fecha->isSunday()) {
            return response()->json(['message' => 'No hay tutorías los domingos.'], 422);
        }

        $disp = Disponibilidad::where('tutor_id', $request->tutor_id)
            ->where('dia_semana', $fecha->dayOfWeekIso)
            ->where('hora_inicio', $request->hora_inicio)->first();

        if (!$disp) {
            return response()->json(['message' => 'El bloque no está disponible.'], 422);
        }

        return DB::transaction(function () use ($request, $estudiante, $fecha, $disp) {
            $ocupado = Tutoria::where('tutor_id', $request->tutor_id)
                ->where('fecha', $fecha->toDateString())
                ->where('hora_inicio', $request->hora_inicio)
                ->where('estado', 'CONFIRMADA')->lockForUpdate()->exists();

            if ($ocupado) {
                return response()->json(['message' => 'El bloque ya fue reservado.'], 422);
            }

            $choque = Tutoria::where('estudiante_id', $estudiante->id)
                ->where('fecha', $fecha->toDateString())
                ->where('hora_inicio', $request->hora_inicio)
                ->where('estado', 'CONFIRMADA')->exists();

            if ($choque) {
                return response()->json(['message' => 'Ya tienes otra tutoría a esa hora.'], 422);
            }

            $tutor = User::with('perfilTutor')->find($request->tutor_id);
            $lugar = $disp->lugar_o_enlace ?? ($disp->modalidad === 'PRESENCIAL' ? $tutor->perfilTutor->lugar_presencial_default : $tutor->perfilTutor->enlace_virtual_default);

            $tutoria = Tutoria::create([
                'estudiante_id' => $estudiante->id,
                'tutor_id' => $request->tutor_id,
                'materia_id' => $request->materia_id,
                'tema' => $request->tema,
                'descripcion' => $request->descripcion,
                'fecha' => $fecha->toDateString(),
                'hora_inicio' => $request->hora_inicio,
                'modalidad' => $disp->modalidad,
                'lugar_o_enlace' => $lugar ?? 'Por definir',
                'estado' => 'CONFIRMADA',
            ]);

            BitacoraService::registrar($estudiante->id, 'TUTORIA_RESERVADA', "Reservó tutoría #{$tutoria->id}", 'Tutoria', $tutoria->id);
            return response()->json(['message' => 'Tutoría reservada.', 'tutoria' => $tutoria], 201);
        });
    }
}
