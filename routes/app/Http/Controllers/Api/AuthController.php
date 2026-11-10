<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CodigoVerificacion;
use App\Services\CodigoVerificacionService;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected CodigoVerificacionService $codigoService) {}

    public function enviarCodigoRegistro(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        if (User::where('email', $request->email)->exists()) {
            throw ValidationException::withMessages(['email' => ['Este correo ya está registrado.']]);
        }
        $this->codigoService->enviarCodigo($request->email, 'VERIFICAR_CORREO');
        return response()->json(['message' => 'Código de verificación enviado a tu correo institucional.']);
    }

    public function verificarCodigoRegistro(Request $request)
    {
        $request->validate(['email' => 'required|email', 'codigo' => 'required|string|size:6']);
        $tokenRegistro = $this->codigoService->verificarCodigo($request->email, 'VERIFICAR_CORREO', $request->codigo);
        return response()->json(['message' => 'Código verificado.', 'registro_token' => $tokenRegistro]);
    }

    public function completarRegistro(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'registro_token' => 'required|string',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'carrera_id' => 'required|exists:carreras,id',
            'telefono' => 'nullable|string',
            'foto_url' => 'required|string',
        ]);

        $registro = CodigoVerificacion::where('correo', $request->email)->where('tipo', 'VERIFICAR_CORREO')->first();

        if (!$registro || !$registro->token_registro_hash ||
            hash('sha256', $request->registro_token) !== $registro->token_registro_hash ||
            $registro->token_registro_expira_at->isPast()) {
            throw ValidationException::withMessages(['registro_token' => ['El token de registro es inválido o ha expirado.']]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => 'ESTUDIANTE',
            'carrera_id' => $request->carrera_id,
            'telefono' => $request->telefono,
            'foto_url' => $request->foto_url,
            'email_verified_at' => now(),
            'activo' => true,
        ]);

        $registro->delete();
        BitacoraService::registrar($user->id, 'USUARIO_REGISTRADO', "{$user->name} se registró.", 'User', $user->id);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['message' => 'Cuenta creada.', 'access_token' => $token, 'token_type' => 'Bearer', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Las credenciales son incorrectas.']]);
        }
        if (!$user->activo) {
            return response()->json(['message' => 'Tu cuenta se encuentra suspendida.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('perfilTutor')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
