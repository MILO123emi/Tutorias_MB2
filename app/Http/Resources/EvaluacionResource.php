<?php

namespace App\Http\Resources;

use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Evaluacion */
class EvaluacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tutoria_id' => $this->tutoria_id,
            'puntuacion' => $this->puntuacion,
            'comentario' => $this->comentario,
            'created_at' => $this->created_at,
        ];
    }
}
