<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class VerificarCodigoRegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('correo'))) {
            $this->merge(['correo' => Str::lower(trim($this->input('correo')))]);
        }
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'string', 'email', 'max:255'],
            // Siempre como texto: si llegara como número se perderían los ceros a la izquierda
            'codigo' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.string' => 'El código debe enviarse como texto de 6 dígitos.',
            'codigo.digits' => 'El código debe tener 6 dígitos.',
        ];
    }
}