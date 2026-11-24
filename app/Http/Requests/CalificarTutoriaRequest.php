<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalificarTutoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('calificar', $this->route('tutoria')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('comentario'))) {
            $comentario = trim($this->input('comentario'));
            $this->merge(['comentario' => $comentario === '' ? null : $comentario]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'puntuacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
            'tutoria_id' => ['prohibited'],
            'estudiante_id' => ['prohibited'],
            'tutor_id' => ['prohibited'],
        ];
    }
}
