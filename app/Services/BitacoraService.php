<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\User;

class BitacoraService
{
    /**
     * Registra un evento de la bitácora (sección 11.1). Lo llaman los servicios,
     * no los controladores. `requiereAtencion` marca los eventos (!) para la admin.
     */
    public function registrar(
        ?User $actor,
        string $tipoEvento,
        ?string $entidad = null,
        ?int $entidadId = null,
        ?string $detalle = null,
        bool $requiereAtencion = false,
    ): Bitacora {
        return Bitacora::create([
            'actor_id' => $actor?->id,
            'tipo_evento' => $tipoEvento,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'detalle' => $detalle,
            'requiere_atencion' => $requiereAtencion,
        ]);
    }
}
