<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Citacion;
use App\Models\User;
use App\Notifications\CitacionRecibida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CitacionService
{
    public function __construct(private BitacoraService $bitacora) {}

    /** @param array<string, mixed> $datos */
    public function crear(User $admin, array $datos): Citacion
    {
        return DB::transaction(function () use ($admin, $datos): Citacion {
            $citacion = Citacion::create([...$datos, 'admin_id' => $admin->id]);

            // Si la citación sale de un evento (!) de la bitácora, ese evento ya quedó atendido.
            if ($citacion->bitacora_id !== null) {
                Bitacora::whereKey($citacion->bitacora_id)->whereNull('revisado_at')->update([
                    'revisado_at' => CarbonImmutable::now(config('app.timezone')),
                    'revisado_por' => $admin->id,
                ]);
            }

            $citacion->load('citado');
            $this->bitacora->registrar($admin, 'CITACION_CREADA', 'citaciones', $citacion->id,
                "Se citó a {$citacion->citado->name}.");
            $citacion->citado->notify(new CitacionRecibida($citacion));

            return $citacion;
        });
    }
}
