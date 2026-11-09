<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class EnviarCodigoRegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Deja el correo en minúsculas y sin espacios antes de validar
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('correo'))) {
            $this->merge(['correo' => Str::lower(trim($this->input('correo')))]);
        }
    }

    public function rules(): array
    {
        return [
            'correo' => [
                'bail',
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) {
                    $dominio = Str::lower(config('tutorias.dominio'));

                    if (! str_ends_with($value, $dominio)) {
                        $fail('Debes usar tu correo institucional (' . config('tutorias.dominio') . ').');
                    }
                },
                'unique:users,email',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'correo.unique' => 'Este correo ya tiene una cuenta.',
        ];
    }
}