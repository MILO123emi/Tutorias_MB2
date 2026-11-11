<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Datos públicos de un tutor aprobado. Nunca incluye comentarios de las evaluaciones
 * ni el teléfono (ese se muestra solo en el detalle de una tutoría reservada).
 */
class TutorResource extends JsonResource
{
    /** El detalle (`GET tutores/{id}`) agrega la bio y las tutorías cumplidas. */
    public bool $mostrarDetalle = false;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $perfil = $this->perfilTutor;
        $total = (int) ($perfil?->total_calificaciones ?? 0);
        $promedio = $perfil?->promedio_calificacion;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'foto_url' => $this->foto_url,
            'carrera' => $this->whenLoaded('carrera', fn () => $this->carrera ? [
                'id' => $this->carrera->id,
                'nombre' => $this->carrera->nombre,
            ] : null),
            'materias' => $this->whenLoaded('materias', fn () => $this->materias->map(fn ($materia) => [
                'id' => $materia->id,
                'nombre' => $materia->nombre,
            ])->values()),
            // Estrellas sobre 5, redondeadas a media estrella.
            'promedio_calificacion' => $promedio !== null ? round(((float) $promedio) * 2) / 2 : null,
            'total_calificaciones' => $total,
            'nuevo' => $total < 3,
            'bio' => $this->when($this->mostrarDetalle, fn () => $perfil?->bio),
            'total_cumplidas' => $this->when($this->mostrarDetalle, fn () => (int) ($perfil?->total_cumplidas ?? 0)),
        ];
    }

    public function conDetalle(): static
    {
        $this->mostrarDetalle = true;

        return $this;
    }
}
