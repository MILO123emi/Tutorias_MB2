<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisponibilidadResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'dia_semana' => $this->dia_semana,
            'hora_inicio' => $this->hora_inicio,
            'modalidad' => $this->modalidad,
            'lugar_o_enlace' => $this->lugar_o_enlace,
        ];
    }
}
