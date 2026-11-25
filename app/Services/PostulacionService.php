<?php

namespace App\Services;

use App\Models\PerfilTutor;
use App\Models\User;
use App\Notifications\PostulacionRevisada;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    /** @return LengthAwarePaginator<int, PerfilTutor> */
    public function pendientes(): LengthAwarePaginator
    {
        return PerfilTutor::where('estado', 'PENDIENTE')
            ->with(['user.carrera', 'user.materias'])
            ->orderBy('created_at')->orderBy('id')->paginate(15);
    }

    /** @param array<int, int>|null $materias */
    public function aprobar(User $admin, PerfilTutor $perfil, ?array $materias): PerfilTutor
    {
        return DB::transaction(function () use ($admin, $perfil, $materias): PerfilTutor {
            $perfil = PerfilTutor::whereKey($perfil->id)->lockForUpdate()->firstOrFail();
            abort_unless($perfil->estado === 'PENDIENTE', 409, 'Esta postulación ya fue revisada.');
            $usuario = User::whereKey($perfil->user_id)->lockForUpdate()->firstOrFail();
            abort_if(! $usuario->activo, 409, 'La cuenta del postulante está suspendida.');

            if ($materias !== null) {
                $usuario->materias()->sync($materias);
            }
            if ($usuario->materias()->count() === 0) {
                throw ValidationException::withMessages(['materias' => 'El tutor necesita al menos una materia.']);
            }

            $perfil->forceFill([
                'estado' => 'APROBADO', 'aprobado_por' => $admin->id,
                'fecha_aprobacion' => CarbonImmutable::now(config('app.timezone')),
                'motivo_rechazo' => null,
            ])->save();
            $this->bitacora->marcarRevisados($admin, 'TUTOR_POSTULADO', 'perfil_tutor', $perfil->id);
            $this->bitacora->registrar($admin, 'TUTOR_APROBADO', 'perfil_tutor', $perfil->id,
                "Aprobó como tutor a {$usuario->name} en ".$usuario->materias()->count().' materias.');
            $usuario->notify(new PostulacionRevisada($perfil, 'APROBADO', null));

            return $perfil->load('user.carrera', 'user.materias');
        }, 3);
    }

    public function rechazar(User $admin, PerfilTutor $perfil, string $motivo): PerfilTutor
    {
        return DB::transaction(function () use ($admin, $perfil, $motivo): PerfilTutor {
            $perfil = PerfilTutor::whereKey($perfil->id)->lockForUpdate()->firstOrFail();
            abort_unless($perfil->estado === 'PENDIENTE', 409, 'Esta postulación ya fue revisada.');
            $usuario = User::whereKey($perfil->user_id)->lockForUpdate()->firstOrFail();
            abort_if(! $usuario->activo, 409, 'La cuenta del postulante está suspendida.');

            $perfil->forceFill([
                'estado' => 'RECHAZADO', 'motivo_rechazo' => $motivo,
                'aprobado_por' => null, 'fecha_aprobacion' => null,
            ])->save();
            $this->bitacora->marcarRevisados($admin, 'TUTOR_POSTULADO', 'perfil_tutor', $perfil->id);
            $this->bitacora->registrar($admin, 'TUTOR_RECHAZADO', 'perfil_tutor', $perfil->id,
                "Rechazó la postulación de {$usuario->name}.");
            $usuario->notify(new PostulacionRevisada($perfil, 'RECHAZADO', $motivo));

            return $perfil->load('user.carrera', 'user.materias');
        }, 3);
    }
}
