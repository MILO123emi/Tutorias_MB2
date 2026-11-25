<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AprobarPostulacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $carreraId = DB::table('perfil_tutor')
            ->join('users', 'users.id', '=', 'perfil_tutor.user_id')
            ->where('perfil_tutor.id', $this->route('perfil'))
            ->value('users.carrera_id');
        abort_if($carreraId === null, 404);

        return [
            'materias' => ['sometimes', 'array', 'min:1', 'max:10'],
            'materias.*' => ['required', 'integer', 'distinct', Rule::exists('materias', 'id')->where('carrera_id', $carreraId)],
            'estado' => ['prohibited'], 'user_id' => ['prohibited'], 'aprobado_por' => ['prohibited'],
            'fecha_aprobacion' => ['prohibited'], 'motivo_rechazo' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['materias.*.exists' => 'Solo puedes asignar materias de la carrera del postulante.'];
    }
}
