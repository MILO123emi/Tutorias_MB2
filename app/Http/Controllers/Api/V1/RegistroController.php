<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnviarCodigoRegistroRequest;
use App\Http\Requests\VerificarCodigoRegistroRequest;
use App\Services\CodigoVerificacionService;
use Illuminate\Http\JsonResponse;

class RegistroController extends Controller
{
    public function __construct(private CodigoVerificacionService $codigos) {}

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
}