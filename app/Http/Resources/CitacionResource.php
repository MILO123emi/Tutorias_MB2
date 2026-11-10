<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mensaje' => $this->mensaje,
            'fecha_cita' => $this->fecha_cita,
            'tutoria_id' => $this->tutoria_id,
            'bitacora_id' => $this->bitacora_id,
            'enterado' => $this->enterado_at !== null,
            'enterado_at' => $this->enterado_at,
            'created_at' => $this->created_at,
        ];
    }
}
