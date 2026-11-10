<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El correo no se incluye a propósito: no se puede cambiar (RF-06).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20'],
            'carrera_id' => ['required', 'integer', 'exists:carreras,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'carrera_id.required' => 'La carrera es obligatoria.',
            'carrera_id.exists' => 'La carrera seleccionada no existe.',
        ];
    }
}
