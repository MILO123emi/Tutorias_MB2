<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class EnviarCodigoRecuperacionRequest extends FormRequest
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

    /**
     * No lleva 'unique': el correo tiene que existir para poder recuperar la cuenta,
     * pero eso no se valida aquí para no revelar qué correos están registrados.
     *
     * @return array<string, mixed>
     */
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
                        $fail('Debes usar tu correo institucional ('.config('tutorias.dominio').').');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no tiene un formato válido.',
        ];
    }
}
