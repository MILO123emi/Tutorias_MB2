<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarDisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esTutorAprobado() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bloques' => ['present', 'array', 'list'],
            'bloques.*' => ['required', 'array:dia_semana,hora_inicio,modalidad,lugar_o_enlace'],
            'bloques.*.dia_semana' => ['required', 'integer', 'between:1,6'],
            'bloques.*.hora_inicio' => ['required', 'integer', 'between:7,20'],
            'bloques.*.modalidad' => ['required', Rule::in(['PRESENCIAL', 'VIRTUAL'])],
            'bloques.*.lugar_o_enlace' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $vistos = [];
            foreach ($this->input('bloques') as $indice => $bloque) {
                $clave = (int) $bloque['dia_semana'].':'.(int) $bloque['hora_inicio'];
                if (isset($vistos[$clave])) {
                    $validator->errors()->add("bloques.$indice.hora_inicio", 'No puedes repetir un bloque del mismo día y hora.');
                }
                $vistos[$clave] = true;
            }
        }];
    }
}
