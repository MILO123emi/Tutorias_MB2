<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RechazarPostulacionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('motivo'))) {
            $this->merge(['motivo' => trim($this->input('motivo'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
            'estado' => ['prohibited'], 'user_id' => ['prohibited'],
            'aprobado_por' => ['prohibited'], 'fecha_aprobacion' => ['prohibited'],
        ];
    }
}
