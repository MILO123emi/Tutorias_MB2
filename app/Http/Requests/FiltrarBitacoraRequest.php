<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarBitacoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipo_evento' => ['nullable', 'string', 'max:50'],
            'actor_id' => ['nullable', 'integer'],
            'entidad' => ['nullable', 'string', 'max:50'],
            'entidad_id' => ['nullable', 'integer'],
            'requiere_atencion' => ['nullable', 'in:0,1,true,false'],
            // Solo los eventos (!) que nadie ha revisado
            'pendientes' => ['nullable', 'in:0,1,true,false'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }
}
