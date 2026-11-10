<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPasswordRequest;
use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\SubirFotoRequest;
use App\Http\Resources\SolicitudFotoResource;
use App\Http\Resources\UserResource;
use App\Services\PerfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function __construct(private PerfilService $perfil) {}

    // GET /api/v1/perfil
    public function show(Request $request): JsonResponse
    {
        return response()->json(['usuario' => new UserResource($request->user())]);
    }

    // PUT /api/v1/perfil
    public function update(ActualizarPerfilRequest $request): JsonResponse
    {
        $usuario = $this->perfil->actualizar($request->user(), $request->validated());

        return response()->json([
            'message' => 'Perfil actualizado.',
            'usuario' => new UserResource($usuario),
        ]);
    }

    // PUT /api/v1/perfil/password
    public function updatePassword(ActualizarPasswordRequest $request): JsonResponse
    {
        $this->perfil->cambiarPassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return response()->json(['message' => 'Contraseña actualizada. Se cerraron las otras sesiones.']);
    }

    // POST /api/v1/perfil/foto
    public function subirFoto(SubirFotoRequest $request): JsonResponse
    {
        $resultado = $this->perfil->subirFoto($request->user(), $request->file('foto'));

        if ($resultado['estado'] === 'APLICADA') {
            return response()->json([
                'message' => 'Foto actualizada.',
                'foto_url' => $resultado['foto_url'],
            ]);
        }

        return response()->json([
            'message' => 'Tu nueva foto quedó pendiente de aprobación.',
            'solicitud' => new SolicitudFotoResource($resultado['solicitud']),
        ], 202);
    }

    // GET /api/v1/perfil/foto/solicitud
    public function solicitudFoto(Request $request): JsonResponse
    {
        $solicitud = $this->perfil->solicitudPendiente($request->user());

        return response()->json([
            'solicitud' => $solicitud ? new SolicitudFotoResource($solicitud) : null,
        ]);
    }
}
