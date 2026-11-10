<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disponibilidad;
use Illuminate\Http\Request;

class DisponibilidadController extends Controller
{
    public function index(Request $request)
    {
        $disponibilidades = Disponibilidad::where('tutor_id', $request->user()->id)->get();
        return response()->json(['disponibilidad' => $disponibilidades]);
    }

    public function guardar(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'bloques' => 'required|array',
            'bloques.*.dia_semana' => 'required|integer|between:1,6',
            'bloques.*.hora_inicio' => 'required|integer|between:7,20',
            'bloques.*.modalidad' => 'required|in:PRESENCIAL,VIRTUAL',
            'bloques.*.lugar_o_enlace' => 'nullable|string',
        ]);

        Disponibilidad::where('tutor_id', $user->id)->delete();

        $nuevosBloques = array_map(fn($b) => [
            'tutor_id' => $user->id,
            'dia_semana' => $b['dia_semana'],
            'hora_inicio' => $b['hora_inicio'],
            'modalidad' => $b['modalidad'],
            'lugar_o_enlace' => $b['lugar_o_enlace'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $request->bloques);

        Disponibilidad::insert($nuevosBloques);
        return response()->json(['message' => 'Disponibilidad actualizada correctamente.']);
    }
}
