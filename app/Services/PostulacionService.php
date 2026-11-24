<?php

namespace App\Services;

use App\Models\PerfilTutor;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PostulacionService
{
    public function __construct(private BitacoraService $bitacora) {}

    /**
     * @param  array{bio?: ?string, lugar_presencial_default?: ?string, enlace_virtual_default?: ?string}  $datos
     * @param  array<int, int>  $materias
     */
    public function postular(User $user, array $datos, array $materias): PerfilTutor
    {
        return DB::transaction(function () use ($user, $datos, $materias): PerfilTutor {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->rol === 'ESTUDIANTE', 403, 'Solo los estudiantes pueden postularse como tutor.');

            $perfil = PerfilTutor::where('user_id', $user->id)->lockForUpdate()->first();
            if ($perfil) {
                match ($perfil->estado) {
                    'PENDIENTE' => abort(409, 'Ya tienes una postulación pendiente de revisión.'),
                    'APROBADO' => abort(409, 'Ya eres tutor aprobado.'),
                    'INACTIVO' => abort(409, 'Tu perfil de tutor está inactivo. Comunícate con Bienestar.'),
                    default => null,
                };
            } else {
                $perfil = new PerfilTutor(['user_id' => $user->id]);
            }

            $perfil->fill(Arr::only($datos, ['bio', 'lugar_presencial_default', 'enlace_virtual_default']));
            $perfil->forceFill([
                'estado' => 'PENDIENTE',
                'motivo_rechazo' => null,
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
            ]);

            try {
                $perfil->save();
            } catch (UniqueConstraintViolationException) {
                abort(409, 'Ya tienes una postulación pendiente de revisión.');
            }

            $user->materias()->sync($materias);
            $this->bitacora->registrar(
                $user,
                'TUTOR_POSTULADO',
                'perfil_tutor',
                $perfil->id,
                'Se postuló como tutor en '.count($materias).' materias.',
                true,
            );

            return $perfil->load('user.materias');
        }, 3);
    }

    public function mia(User $user): ?PerfilTutor
    {
        return PerfilTutor::where('user_id', $user->id)->with('user.materias')->first();
    }
}
