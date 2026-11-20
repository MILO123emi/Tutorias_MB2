<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TutoriaResource extends JsonResource
{
    private bool $incluirContacto = false;

    public function conContacto(): static
    {
        $this->incluirContacto = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tema' => $this->tema,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'hora_inicio' => $this->hora_inicio,
            'hora_fin' => $this->hora_inicio + 1,
            'modalidad' => $this->modalidad,
            'lugar_o_enlace' => $this->lugar_o_enlace,
            'estado' => $this->estado,
            'asistencia_confirmada' => $this->asistencia_confirmada_at !== null,
            'materia' => $this->whenLoaded('materia', fn () => [
                'id' => $this->materia->id,
                'nombre' => $this->materia->nombre,
            ]),
            'tutor' => $this->whenLoaded('tutor', fn () => [
                'id' => $this->tutor->id,
                'name' => $this->tutor->name,
                'foto_url' => $this->tutor->foto_url,
                'telefono' => $this->when($this->incluirContacto, fn () => $this->tutor->telefono),
            ]),
            'estudiante' => $this->whenLoaded('estudiante', fn () => [
                'id' => $this->estudiante->id,
                'name' => $this->estudiante->name,
                'foto_url' => $this->estudiante->foto_url,
                'telefono' => $this->when($this->incluirContacto, fn () => $this->estudiante->telefono),
            ]),
            'calificada' => $this->whenLoaded('evaluacion', fn () => $this->evaluacion !== null),
        ];
    }
}
