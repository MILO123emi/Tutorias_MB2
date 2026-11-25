<?php

namespace App\Http\Resources;

use App\Models\SolicitudFoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SolicitudFoto */
class SolicitudFotoResource extends JsonResource
{
    private bool $incluirUsuario = false;

    public function conUsuario(): static
    {
        $this->incluirUsuario = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'foto_nueva_url' => $this->foto_nueva_url,
            'estado' => $this->estado,
            'motivo_rechazo' => $this->motivo_rechazo,
            'created_at' => $this->created_at,
            'usuario' => $this->when($this->incluirUsuario && $this->resource->relationLoaded('user'), fn () => [
                'id' => data_get($this->resource->getRelation('user'), 'id'),
                'name' => data_get($this->resource->getRelation('user'), 'name'),
                'email' => data_get($this->resource->getRelation('user'), 'email'),
                'foto_actual_url' => data_get($this->resource->getRelation('user'), 'foto_url'),
                'es_tutor_aprobado' => data_get($this->resource->getRelation('user'), 'perfilTutor.estado') === 'APROBADO',
            ]),
        ];
    }
}
