<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitarPostulacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'ESTUDIANTE';
    }

    protected function failedAuthorization(): void
    {
        abort(403, 'Solo los estudiantes pueden postularse como tutor.');
    }

    protected function prepareForValidation(): void
    {
        foreach (['bio', 'lugar_presencial_default', 'enlace_virtual_default'] as $campo) {
            if (is_string($this->input($campo))) {
                $valor = trim($this->input($campo));
                $this->merge([$campo => $valor === '' ? null : $valor]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:1000'],
            'lugar_presencial_default' => ['nullable', 'string', 'max:255'],
            'enlace_virtual_default' => ['nullable', 'string', 'url', 'max:255'],
            'materias' => ['required', 'array', 'min:1', 'max:10'],
            'materias.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('materias', 'id')->where('carrera_id', $this->user()?->carrera_id),
            ],
            'estado' => ['prohibited'],
            'user_id' => ['prohibited'],
            'motivo_rechazo' => ['prohibited'],
            'aprobado_por' => ['prohibited'],
            'fecha_aprobacion' => ['prohibited'],
            'promedio_calificacion' => ['prohibited'],
            'total_calificaciones' => ['prohibited'],
            'total_cumplidas' => ['prohibited'],
            'total_no_realizadas' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'materias.*.exists' => 'Solo puedes postularte en materias de tu carrera.',
        ];
    }
}
