<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudFotoResource extends JsonResource
{
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
        ];
    }
}
