<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerfilTutor;
use App\Models\TutoriaNoRealizada;
use App\Models\Bitacora;
use App\Services\BitacoraService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function resumen()
    {
        return response()->json([
            'aprobaciones_pendientes' => PerfilTutor::where('estado', 'PENDIENTE')->count(),
            'no_realizadas_sin_revisar' => TutoriaNoRealizada::whereNull('revisado_at')->count(),
            'eventos_atencion' => Bitacora::where('requiere_atencion', true)->whereNull('revisado_at')->count(),
        ]);
    }

    public function aprobarTutor(Request $request, $id)
    {
        $perfil = PerfilTutor::where('user_id', $id)->firstOrFail();
        $perfil->update([
            'estado' => 'APROBADO',
            'aprobado_por' => $request->user()->id,
            'fecha_aprobacion' => now(),
        ]);
        BitacoraService::registrar($request->user()->id, 'TUTOR_APROBADO', "Aprobó al tutor #{$id}", 'PerfilTutor', $id);
        return response()->json(['message' => 'Tutor aprobado.']);
    }

    public function noRealizadas(Request $request)
    {
        $query = TutoriaNoRealizada::with(['tutoria.tutor', 'tutoria.estudiante', 'tutoria.materia']);
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        return response()->json($query->orderByDesc('created_at')->paginate(15));
    }

    public function marcarNoRealizadaRevisada(Request $request, $id)
    {
        $registro = TutoriaNoRealizada::findOrFail($id);
        $registro->update(['revisado_at' => now(), 'revisado_por' => $request->user()->id]);
        return response()->json(['message' => 'Marcado como revisado.']);
    }

    public function bitacora(Request $request)
    {
        $query = Bitacora::query();
        if ($request->boolean('atencion')) {
            $query->where('requiere_atencion', true)->whereNull('revisado_at');
        }
        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }
}
