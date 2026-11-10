<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompletarRegistroRequest;
use App\Http\Requests\EnviarCodigoRegistroRequest;
use App\Http\Requests\VerificarCodigoRegistroRequest;
use App\Http\Resources\UserResource;
use App\Services\CodigoVerificacionService;
use App\Services\CuentaService;
use Illuminate\Http\JsonResponse;

class RegistroController extends Controller
{
    public function __construct(
        private CodigoVerificacionService $codigos,
        private CuentaService $cuentas,
    ) {}

    // POST /api/v1/auth/registro/enviar-codigo
    public function enviarCodigo(EnviarCodigoRegistroRequest $request): JsonResponse
    {
        $this->codigos->enviarCodigo($request->validated('correo'), 'VERIFICAR_CORREO');

        return response()->json([
            'message' => 'Te enviamos un código de 6 dígitos a tu correo.',
            'expira_en_minutos' => config('tutorias.codigo_expira_minutos'),
            'reenviar_en_segundos' => config('tutorias.codigo_reenvio_segundos'),
        ]);
    }

    // POST /api/v1/auth/registro/verificar-codigo
    public function verificarCodigo(VerificarCodigoRegistroRequest $request): JsonResponse
    {
        $token = $this->codigos->verificarCodigo(
            $request->validated('correo'),
            $request->validated('codigo'),
            'VERIFICAR_CORREO'
        );

        return response()->json([
            'message' => 'Correo verificado. Ahora completa tu perfil.',
            'registro_token' => $token,
            'expira_en_minutos' => config('tutorias.registro_token_minutos'),
        ]);
    }

    // POST /api/v1/auth/registro/completar
    public function completar(CompletarRegistroRequest $request): JsonResponse
    {
        $resultado = $this->cuentas->completarRegistro(
            $request->validated('registro_token'),
            $request->safe()->only(['name', 'carrera_id', 'telefono', 'password']),
            $request->validated('device_name') ?? 'app',
        );

        return response()->json([
            'message' => 'Tu cuenta quedó lista.',
            'token' => $resultado['token'],
            'usuario' => new UserResource($resultado['user']),
        ], 201);
    }
}