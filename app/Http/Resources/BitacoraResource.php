<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BitacoraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_evento' => $this->tipo_evento,
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'detalle' => $this->detalle,
            // null cuando lo generó el sistema
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'rol' => $this->actor->rol,
            ]),
            'requiere_atencion' => $this->requiere_atencion,
            'revisado' => $this->revisado_at !== null,
            'revisado_at' => $this->revisado_at,
            'revisado_por' => $this->whenLoaded('revisor', fn () => [
                'id' => $this->revisor->id,
                'name' => $this->revisor->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
