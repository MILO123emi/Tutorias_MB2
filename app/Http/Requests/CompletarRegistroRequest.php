<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompletarRegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Deja el teléfono solo con dígitos antes de validar (acepta "1234-5678").
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('telefono')) {
            $this->merge([
                'telefono' => preg_replace('/\D/', '', (string) $this->input('telefono')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registro_token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'carrera_id' => ['required', 'integer', 'exists:carreras,id'],
            'telefono' => ['required', 'string', 'regex:/^\d{8}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registro_token.required' => 'Falta el registro. Verifica tu correo de nuevo.',
            'name.required' => 'El nombre es obligatorio.',
            'carrera_id.required' => 'La carrera es obligatoria.',
            'carrera_id.exists' => 'La carrera seleccionada no existe.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe tener 8 dígitos (ej: 1234-5678).',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
