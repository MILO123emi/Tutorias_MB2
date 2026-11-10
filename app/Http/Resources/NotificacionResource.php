<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class NotificacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => class_basename(Str::of($this->type)->replace('\\', '/')),
            'data' => $this->data,
            'leida' => $this->read_at !== null,
            'leida_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
