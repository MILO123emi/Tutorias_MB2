<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida el motivo privado de una inasistencia. Solo lo ve administración.
class ReportarInasistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reportarInasistencia', $this->route('tutoria')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('motivo'))) {
            $this->merge(['motivo' => trim($this->input('motivo'))]);
        }
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:500']];
    }
}
