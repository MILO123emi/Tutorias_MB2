<?php

namespace App\Http\Requests;

use App\Models\Tutoria;
use Illuminate\Foundation\Http\FormRequest;

class ReservarTutoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tutoria::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tutor_id' => ['required', 'integer', 'exists:users,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'integer', 'between:7,20'],
            'materia_id' => ['required', 'integer', 'exists:materias,id'],
            'tema' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'modalidad' => ['prohibited'],
            'lugar_o_enlace' => ['prohibited'],
            'estudiante_id' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }
}
