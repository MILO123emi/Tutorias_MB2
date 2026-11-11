<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PerfilTutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerfilTutorController extends Controller
{
    // Crear o solicitar perfil de tutor
    public function store(Request $request)
    {
        $user = Auth::user();

        $perfilExistente = PerfilTutor::where('user_id', $user->id)->first();
        if ($perfilExistente) {
            return response()->json([
                'message' => 'Ya cuentas con una solicitud o perfil de tutor registrado.',
                'perfil' => $perfilExistente
            ], 400);
        }

        $request->validate([
            'bio' => 'nullable|string',
            'lugar_presencial_default' => 'nullable|string|max:255',
            'enlace_virtual_default' => 'nullable|string|max:255',
        ]);

        $perfilTutor = PerfilTutor::create([
            'user_id' => $user->id,
            'estado' => 'PENDIENTE', // Valor por defecto en tu BDD
            'bio' => $request->bio,
            'lugar_presencial_default' => $request->lugar_presencial_default,
            'enlace_virtual_default' => $request->enlace_virtual_default,
        ]);

        return response()->json([
            'message' => 'Solicitud de perfil de tutor enviada con éxito.',
            'data' => $perfilTutor
        ], 201);
    }

    // Consultar el estado actual del perfil del usuario logueado
    public function miEstado(Request $request)
    {
        $perfil = PerfilTutor::where('user_id', Auth::id())->first();

        if (!$perfil) {
            return response()->json(['message' => 'No tienes un perfil de tutor registrado.'], 404);
        }

        return response()->json(['data' => $perfil]);
    }
    // Sincronizar / Guardar las materias que imparte el tutor

    public function sincronizarMaterias(Request $request)
    {
        $user = Auth::user();
        $perfilTutor = PerfilTutor::where('user_id', $user->id)->first();

        if (!$perfilTutor) {
            return response()->json(['message' => 'No tienes un perfil de tutor registrado.'], 404);
        }

        $request->validate([
            'materias' => 'required|array',
            'materias.*' => 'exists:materias,id' // Valida que los IDs de las materias existan en la BDD
        ]);

        $perfilTutor->materias()->sync($request->materias);

        return response()->json([
            'message' => 'Materias del tutor actualizadas con éxito.',
            'materias' => $perfilTutor->materias
        ]);
    }

    public function obtenerMaterias(Request $request)
    {
        $user = Auth::user();
        $perfilTutor = PerfilTutor::where('user_id', $user->id)->first();

        if (!$perfilTutor) {
            return response()->json(['message' => 'No tienes un perfil de tutor registrado.'], 404);
        }

        return response()->json([
            'materias' => $perfilTutor->materias
        ]);
    }
}
