<?php

namespace App\Http\Resources;

use App\Models\PerfilTutor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PerfilTutor */
class PerfilTutorResource extends JsonResource
{
    private bool $incluirPostulante = false;

    public function conPostulante(): static
    {
        $this->incluirPostulante = true;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'motivo_rechazo' => $this->motivo_rechazo,
            'bio' => $this->bio,
            'lugar_presencial_default' => $this->lugar_presencial_default,
            'enlace_virtual_default' => $this->enlace_virtual_default,
            'fecha_aprobacion' => $this->fecha_aprobacion,
            'materias' => $this->whenLoaded('user', fn () => MateriaResource::collection(
                $this->resource->getRelation('user')->getRelation('materias')
            )),
            'created_at' => $this->created_at,
            'postulante' => $this->when($this->incluirPostulante && $this->resource->relationLoaded('user'), fn () => [
                'id' => data_get($this->resource->getRelation('user'), 'id'),
                'name' => data_get($this->resource->getRelation('user'), 'name'),
                'email' => data_get($this->resource->getRelation('user'), 'email'),
                'telefono' => data_get($this->resource->getRelation('user'), 'telefono'),
                'foto_url' => data_get($this->resource->getRelation('user'), 'foto_url'),
                'carrera' => data_get($this->resource->getRelation('user'), 'carrera') ? [
                    'id' => data_get($this->resource->getRelation('user'), 'carrera.id'),
                    'nombre' => data_get($this->resource->getRelation('user'), 'carrera.nombre'),
                ] : null,
            ]),
        ];
    }
}
