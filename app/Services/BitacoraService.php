<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\User;
use Carbon\CarbonImmutable;

class BitacoraService
{
    public function marcarRevisados(User $admin, string $tipoEvento, string $entidad, int $entidadId): int
    {
        return Bitacora::where('tipo_evento', $tipoEvento)
            ->where('entidad', $entidad)
            ->where('entidad_id', $entidadId)
            ->whereNull('revisado_at')
            ->update([
                'revisado_at' => CarbonImmutable::now(config('app.timezone')),
                'revisado_por' => $admin->id,
            ]);
    }

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
            'detalle' => $detalle ?? $tipoEvento,
            'requiere_atencion' => $requiereAtencion,
        ]);
    }
}
