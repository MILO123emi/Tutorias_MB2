<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutoria;
use App\Models\TutoriaNoRealizada;
use App\Models\Evaluacion;
use App\Models\PerfilTutor;
use App\Services\BitacoraService;
use Illuminate\Http\Request;

class GestionTutoriaController extends Controller
{
    public function misTutorias(Request $request)
    {
        $tutorias = Tutoria::where('estudiante_id', $request->user()->id)
            ->with(['tutor', 'materia'])->orderByDesc('fecha')->paginate(15);
        return response()->json($tutorias);
    }

    public function miAgenda(Request $request)
    {
        $tutorias = Tutoria::where('tutor_id', $request->user()->id)
            ->with(['estudiante.carrera', 'materia'])->orderBy('fecha')->paginate(15);
        return response()->json($tutorias);
    }

    public function confirmarAsistencia(Request $request, $id)
    {
        $tutoria = Tutoria::where('id', $id)->where('estudiante_id', $request->user()->id)->firstOrFail();
        if ($tutoria->estado !== 'CONFIRMADA') {
            return response()->json(['message' => 'La tutoría no está en estado CONFIRMADA.'], 422);
        }

        $tutoria->update(['estado' => 'COMPLETADA', 'asistencia_confirmada_at' => now()]);
        PerfilTutor::where('user_id', $tutoria->tutor_id)->increment('total_cumplidas');
        BitacoraService::registrar($request->user()->id, 'TUTORIA_COMPLETADA', "Confirmó asistencia a tutoría #{$tutoria->id}", 'Tutoria', $tutoria->id);

        return response()->json(['message' => 'Asistencia confirmada. Ya puedes evaluar al tutor.']);
    }

    public function cancelar(Request $request, $id)
    {
        $tutoria = Tutoria::findOrFail($id);
        $user = $request->user();

        if ($tutoria->estado !== 'CONFIRMADA') {
            return response()->json(['message' => 'Solo se pueden cancelar tutorías confirmadas.'], 422);
        }

        $esTutor = $tutoria->tutor_id === $user->id;
        $esEstudiante = $tutoria->estudiante_id === $user->id;

        if (!$esTutor && !$esEstudiante) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $request->validate([
            'categoria_motivo' => 'nullable|in:ENFERMEDAD,EVENTO_ACADEMICO,CHOQUE_HORARIO,EMERGENCIA,OTRO',
            'motivo_para_admin' => $esTutor ? 'required|string' : 'nullable|string',
            'motivo_para_contraparte' => 'required|string',
        ]);

        $nuevoEstado = $esTutor ? 'CANCELADA_TUTOR' : 'CANCELADA_ESTUDIANTE';
        $tutoria->update(['estado' => $nuevoEstado]);

        TutoriaNoRealizada::create([
            'tutoria_id' => $tutoria->id,
            'tipo' => $nuevoEstado,
            'reportado_por' => $user->id,
            'categoria_motivo' => $request->categoria_motivo ?? 'OTRO',
            'motivo_para_admin' => $request->motivo_para_admin,
            'motivo_para_contraparte' => $request->motivo_para_contraparte,
        ]);

        PerfilTutor::where('user_id', $tutoria->tutor_id)->increment('total_no_realizadas');
        BitacoraService::registrar($user->id, "TUTORIA_{$nuevoEstado}", "Canceló tutoría #{$tutoria->id}", 'Tutoria', $tutoria->id, $esTutor);

        return response()->json(['message' => 'Tutoría cancelada correctamente.']);
    }

    public function evaluar(Request $request, $id)
    {
        $tutoria = Tutoria::where('id', $id)->where('estudiante_id', $request->user()->id)->firstOrFail();
        if ($tutoria->estado !== 'COMPLETADA') {
            return response()->json(['message' => 'Solo puedes evaluar tutorías completadas.'], 422);
        }

        $request->validate(['puntuacion' => 'required|integer|between:1,10', 'comentario' => 'nullable|string']);

        Evaluacion::create([
            'tutoria_id' => $tutoria->id,
            'estudiante_id' => $request->user()->id,
            'tutor_id' => $tutoria->tutor_id,
            'puntuacion' => $request->puntuacion,
            'comentario' => $request->comentario,
        ]);

        $promedio = Evaluacion::where('tutor_id', $tutoria->tutor_id)->avg('puntuacion');
        $total = Evaluacion::where('tutor_id', $tutoria->tutor_id)->count();

        PerfilTutor::where('user_id', $tutoria->tutor_id)->update([
            'promedio_calificacion' => round($promedio, 2),
            'total_calificaciones' => $total,
        ]);

        return response()->json(['message' => 'Evaluación registrada. ¡Gracias!']);
    }
}
