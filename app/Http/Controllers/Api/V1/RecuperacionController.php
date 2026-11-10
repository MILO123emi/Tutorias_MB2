<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\EsperaCodigoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EnviarCodigoRecuperacionRequest;
use App\Http\Requests\RestablecerPasswordRequest;
use App\Models\User;
use App\Services\CodigoVerificacionService;
use App\Services\CuentaService;
use Illuminate\Http\JsonResponse;

class RecuperacionController extends Controller
{
    public function __construct(
        private CodigoVerificacionService $codigos,
        private CuentaService $cuentas,
    ) {}

    // POST /api/v1/auth/recuperar/enviar-codigo
    public function enviarCodigo(EnviarCodigoRecuperacionRequest $request): JsonResponse
    {
        $correo = $request->validated('correo');

        // Solo se envía si la cuenta existe, pero la respuesta es siempre la misma
        // para no revelar qué correos están registrados. La espera entre envíos se
        // traga en silencio por el mismo motivo.
        if (User::where('email', $correo)->exists()) {
            try {
                $this->codigos->enviarCodigo($correo, 'RECUPERAR_PASS');
            } catch (EsperaCodigoException) {
                // se ignora a propósito
            }
        }

        return response()->json([
            'message' => 'Si el correo tiene una cuenta, te enviamos un código para recuperarla.',
        ]);
    }

    // POST /api/v1/auth/recuperar/restablecer
    public function restablecer(RestablecerPasswordRequest $request): JsonResponse
    {
        $this->cuentas->restablecerPassword(
            $request->validated('correo'),
            $request->validated('codigo'),
            $request->validated('password'),
        );

        return response()->json([
            'message' => 'Tu contraseña fue actualizada. Inicia sesión de nuevo.',
        ]);
    }
}
