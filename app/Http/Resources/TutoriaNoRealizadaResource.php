<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TutoriaNoRealizadaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'categoria_motivo' => $this->categoria_motivo,
            'motivo_para_admin' => $this->motivo_para_admin,
            'motivo_para_contraparte' => $this->motivo_para_contraparte,
            'reportado_por' => $this->whenLoaded('reportador', fn () => [
                'id' => $this->reportador->id,
                'name' => $this->reportador->name,
            ]),
            'revisado' => $this->revisado_at !== null,
            'revisado_at' => $this->revisado_at,
            'revisado_por' => $this->whenLoaded('revisor', fn () => [
                'id' => $this->revisor->id,
                'name' => $this->revisor->name,
            ]),
            'tutoria' => $this->whenLoaded('tutoria', fn () => (new TutoriaResource($this->tutoria))->conContacto()),
            'created_at' => $this->created_at,
        ];
    }
}
