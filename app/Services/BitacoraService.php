<?php

namespace App\Services;

use App\Models\Bitacora;

class BitacoraService
{
    public static function registrar(int $actorId, string $tipoEvento, string $detalle, ?string $entidad = null, ?int $entidadId = null, bool $requiereAtencion = false): Bitacora
    {
        return Bitacora::create([
            'actor_id' => $actorId,
            'tipo_evento' => $tipoEvento,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'detalle' => $detalle,
            'requiere_atencion' => $requiereAtencion,
        ]);
    }
}
