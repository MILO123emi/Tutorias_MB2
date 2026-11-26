<?php

namespace App\Http\Requests;

use App\Models\Tutoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CrearCitacionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('mensaje'))) {
            $this->merge(['mensaje' => trim($this->input('mensaje'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id'), Rule::notIn([$this->user()?->id])],
            'mensaje' => ['required', 'string', 'min:10', 'max:1000'],
            'fecha_cita' => ['nullable', 'date', 'after:now'],
            'tutoria_id' => ['nullable', 'integer', Rule::exists('tutorias', 'id')],
            'bitacora_id' => ['nullable', 'integer', Rule::exists('bitacora', 'id')],
            'admin_id' => ['prohibited'], 'enterado_at' => ['prohibited'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('tutoria_id')) {
                return;
            }

            $citado = $this->integer('user_id');
            $participa = Tutoria::whereKey($this->integer('tutoria_id'))
                ->where(fn ($q) => $q->where('estudiante_id', $citado)->orWhere('tutor_id', $citado))
                ->exists();

            if (! $participa) {
                $validator->errors()->add('tutoria_id', 'La persona citada no participa en esa tutoría.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.not_in' => 'No puedes citarte a ti mismo.',
            'fecha_cita.after' => 'La fecha de la cita debe ser posterior a este momento.',
        ];
    }
}
