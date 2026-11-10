<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CuentaService
{
    public function __construct(private CodigoVerificacionService $codigos) {}

    /**
     * Completa el registro: consume el registro_token (un solo uso), crea el usuario
     * ESTUDIANTE ya verificado y devuelve el usuario con un token de Sanctum.
     *
     * @param  array{name: string, carrera_id: int, telefono: string, password: string}  $datos
     * @return array{user: User, token: string}
     */
    public function completarRegistro(string $token, array $datos, string $dispositivo): array
    {
        return DB::transaction(function () use ($token, $datos, $dispositivo) {
            $registro = $this->codigos->validarTokenRegistro($token, 'VERIFICAR_CORREO');

            // 'rol' y 'activo' no están en fillable: se asignan con forceFill a propósito.
            $user = new User;
            $user->forceFill([
                'name' => $datos['name'],
                'email' => $registro->correo,
                'password' => $datos['password'],   // el cast 'hashed' del modelo la guarda con hash
                'carrera_id' => $datos['carrera_id'],
                'telefono' => $datos['telefono'],
                'rol' => 'ESTUDIANTE',
                'activo' => true,
                'email_verified_at' => now(),       // la cuenta nace verificada
            ])->save();

            $this->codigos->consumirTokenRegistro($registro);

            return [
                'user' => $user,
                'token' => $user->createToken($dispositivo)->plainTextToken,
            ];
        });
    }

    /**
     * Valida las credenciales y devuelve el usuario con un token de Sanctum.
     * Lanza 422 si no coinciden y 403 si la cuenta está suspendida.
     *
     * @return array{user: User, token: string}
     */
    public function login(string $email, string $password, string $dispositivo): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no coinciden.'],
            ]);
        }

        if (! $user->activo) {
            abort(403, 'Tu cuenta está suspendida. Comunícate con Bienestar.');
        }

        return [
            'user' => $user,
            'token' => $user->createToken($dispositivo)->plainTextToken,
        ];
    }

    /**
     * Verifica el código de recuperación, cambia la contraseña y cierra todas las
     * sesiones de la app (borra los tokens de Sanctum).
     */
    public function restablecerPassword(string $correo, string $codigo, string $password): void
    {
        $this->codigos->verificarCodigo($correo, $codigo, 'RECUPERAR_PASS');

        $user = User::where('email', $correo)->first();

        if ($user) {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
        }
    }
}
