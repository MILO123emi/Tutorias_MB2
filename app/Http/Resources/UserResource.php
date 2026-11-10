<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
            'carrera_id' => $this->carrera_id,
            'telefono' => $this->telefono,
            'foto_url' => $this->foto_url,
            'activo' => $this->activo,
            'es_tutor_aprobado' => $this->esTutorAprobado(),
        ];
    }
}
