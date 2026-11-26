<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarNoRealizadasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipo' => ['nullable', Rule::in([
                'CANCELADA_TUTOR', 'CANCELADA_ESTUDIANTE', 'NO_ASISTIO_ESTUDIANTE', 'NO_ASISTIO_TUTOR', 'SIN_CONFIRMAR',
            ])],
            'revisado' => ['nullable', 'in:0,1,true,false'],
            // El rango es sobre la fecha de la tutoría, no la del reporte
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'tutor_id' => ['nullable', 'integer'],
            'estudiante_id' => ['nullable', 'integer'],
            'materia_id' => ['nullable', 'integer'],
        ];
    }
}
