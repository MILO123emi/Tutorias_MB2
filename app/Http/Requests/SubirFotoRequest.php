<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * jpg/png/webp, máximo 5 MB (5120 KB) (RNF-05).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Tenés que elegir una imagen.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'La imagen debe ser jpg, png o webp.',
            'foto.max' => 'La imagen no puede pesar más de 5 MB.',
        ];
    }
}
