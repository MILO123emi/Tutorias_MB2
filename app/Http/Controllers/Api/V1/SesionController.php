<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\CuentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    public function __construct(private CuentaService $cuentas) {}

    // POST /api/v1/auth/login
    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->cuentas->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name') ?? 'app',
        );

        return response()->json([
            'message' => 'Sesión iniciada.',
            'token' => $resultado['token'],
            'usuario' => new UserResource($resultado['user']),
        ]);
    }

    // POST /api/v1/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    // GET /api/v1/auth/yo
    public function yo(Request $request): JsonResponse
    {
        return response()->json(['usuario' => new UserResource($request->user())]);
    }
}
