<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PerfilTutor;
use App\Models\Tutoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutoriaController extends Controller
{
    public function index(Request $request)
    {
        $tutorias = Tutoria::with(['materia'])->paginate(15);
        return response()->json($tutorias);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Validamos según el JSON que envías en Bruno
        $request->validate([
            'estudiante_id' => 'required|exists:users,id',
            'tutor_id' => 'required|exists:users,id',
            'materia_id' => 'required|exists:materias,id',
            'tema' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|integer',
            'modalidad' => 'required|in:PRESENCIAL,VIRTUAL',
            'lugar_o_enlace' => 'nullable|string|max:255',
            'estado' => 'nullable|string'
        ]);

        $tutoria = Tutoria::create([
            'estudiante_id' => $request->estudiante_id,
            'tutor_id' => $request->tutor_id, // Usamos directamente el tutor_id que viene en el request o del usuario logueado
            'materia_id' => $request->materia_id,
            'tema' => $request->tema,
            'descripcion' => $request->descripcion,
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'modalidad' => $request->modalidad,
            'lugar_o_enlace' => $request->lugar_o_enlace,
            'estado' => $request->estado ?? 'CONFIRMADA',
        ]);

        return response()->json([
            'message' => 'Tutoría registrada con éxito.',
            'data' => $tutoria
        ], 201);
    }
}
