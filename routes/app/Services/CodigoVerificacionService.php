<?php

namespace App\Services;

use App\Models\CodigoVerificacion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CodigoVerificacionService
{
    public function enviarCodigo(string $correo, string $tipo): void
    {
        $dominio = env('APP_UNIVERSITY_DOMAIN', 'unicah.edu');
        if (!str_ends_with(strtolower($correo), '@' . $dominio)) {
            throw ValidationException::withMessages([
                'correo' => ["Solo se permiten correos institucionales (@{$dominio})."]
            ]);
        }

        $existente = CodigoVerificacion::where('correo', $correo)->where('tipo', $tipo)->first();
        if ($existente && $existente->ultimo_envio_at && $existente->ultimo_envio_at->diffInSeconds(now()) < 60) {
            throw ValidationException::withMessages([
                'correo' => ['Debes esperar 60 segundos antes de pedir otro código.']
            ]);
        }

        $codigo = (string) random_int(100000, 999999);

        CodigoVerificacion::updateOrCreate(
            ['correo' => $correo, 'tipo' => $tipo],
            [
                'codigo_hash' => Hash::make($codigo),
                'intentos' => 0,
                'expira_at' => now()->addMinutes(15),
                'usado_at' => null,
                'ultimo_envio_at' => now(),
            ]
        );

        \Log::info("Código [{$tipo}] para {$correo}: {$codigo}");
    }

    public function verificarCodigo(string $correo, string $tipo, string $codigo): string
    {
        $registro = CodigoVerificacion::where('correo', $correo)->where('tipo', $tipo)->first();

        if (!$registro || $registro->usado_at || $registro->expira_at->isPast()) {
            throw ValidationException::withMessages(['codigo' => ['El código es inválido o ha expirado.']]);
        }

        if ($registro->intentos >= 5) {
            throw ValidationException::withMessages(['codigo' => ['Demasiados intentos fallidos. Solicita un nuevo código.']]);
        }

        if (!Hash::check($codigo, $registro->codigo_hash)) {
            $registro->increment('intentos');
            $restantes = 5 - $registro->intentos;
            throw ValidationException::withMessages(['codigo' => ["Código incorrecto. Te quedan {$restantes} intentos."]]);
        }

        $tokenRegistro = Str::random(64);
        $registro->update([
            'usado_at' => now(),
            'token_registro_hash' => hash('sha256', $tokenRegistro),
            'token_registro_expira_at' => now()->addMinutes(30),
        ]);

        return $tokenRegistro;
    }
}
