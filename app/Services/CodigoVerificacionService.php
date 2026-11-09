<?php

namespace App\Services;

use App\Exceptions\EsperaCodigoException;
use App\Mail\CodigoVerificacionMail;
use App\Models\CodigoVerificacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CodigoVerificacionService
{
    /**
     * Genera un código de 6 dígitos, guarda su hash y lo envía por correo (por cola).
     * Lanza EsperaCodigoException si pidieron otro código hace menos de 60 s.
     */
    public function enviarCodigo(string $correo, string $tipo): void
    {
        $registro = CodigoVerificacion::where('correo', $correo)
            ->where('tipo', $tipo)
            ->first();

        // Espera mínima entre envíos
        if ($registro?->ultimo_envio_at) {
            $transcurrido = now()->timestamp - $registro->ultimo_envio_at->timestamp;
            $espera = config('tutorias.codigo_reenvio_segundos') - $transcurrido;

            if ($espera > 0) {
                throw new EsperaCodigoException($espera);
            }
        }

        // 6 dígitos con random_int, conservando los ceros a la izquierda
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Un solo registro por (correo, tipo): un código nuevo reemplaza al anterior
        CodigoVerificacion::updateOrCreate(
            ['correo' => $correo, 'tipo' => $tipo],
            [
                'codigo_hash' => Hash::make($codigo),
                'intentos' => 0,
                'expira_at' => now()->addMinutes(config('tutorias.codigo_expira_minutos')),
                'usado_at' => null,
                'ultimo_envio_at' => now(),
                'token_registro_hash' => null,
                'token_registro_expira_at' => null,
            ]
        );

        Mail::to($correo)->queue(new CodigoVerificacionMail($codigo, $tipo));
    }

    /**
     * Verifica el código. Si es correcto, lo marca como usado y devuelve un token
     * de un solo uso (en claro, solo se entrega esta vez; en la BD queda su hash).
     * Si falla, lanza un 422 con el mensaje correspondiente.
     */
    public function verificarCodigo(string $correo, string $codigo, string $tipo): string
    {
        $maxIntentos = config('tutorias.codigo_max_intentos');

        // Registro bloqueado dentro de la transacción: dos peticiones a la vez no se saltan el límite.
        // Los errores NO se lanzan aquí dentro: si no, la transacción se revertiría
        // y el intento fallido no quedaría contado.
        $resultado = DB::transaction(function () use ($correo, $codigo, $tipo, $maxIntentos) {
            $registro = CodigoVerificacion::where('correo', $correo)
                ->where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            if (! $registro) {
                return ['error' => 'No hay un código pendiente para este correo. Pide uno nuevo.'];
            }

            if ($registro->usado_at) {
                return ['error' => 'Este código ya fue usado. Pide uno nuevo.'];
            }

            if ($registro->expira_at->isPast()) {
                return ['error' => 'El código venció. Pide uno nuevo.'];
            }

            if ($registro->intentos >= $maxIntentos) {
                return ['error' => 'Superaste el máximo de intentos. Pide un código nuevo.'];
            }

            if (! Hash::check($codigo, $registro->codigo_hash)) {
                $registro->increment('intentos');
                $restantes = $maxIntentos - $registro->intentos;

                if ($restantes <= 0) {
                    return ['error' => 'Superaste el máximo de intentos. Pide un código nuevo.'];
                }

                return ['error' => "Código incorrecto. Te quedan {$restantes} "
                    . ($restantes === 1 ? 'intento.' : 'intentos.')];
            }

            // Código correcto: se marca como usado y se genera el token de registro
            $token = Str::random(64);

            $registro->update([
                'usado_at' => now(),
                'token_registro_hash' => hash('sha256', $token),
                'token_registro_expira_at' => now()->addMinutes(config('tutorias.registro_token_minutos')),
            ]);

            return ['token' => $token];
        });

        if (isset($resultado['token'])) {
            return $resultado['token'];
        }

        throw ValidationException::withMessages(['codigo' => [$resultado['error']]]);
    }
}