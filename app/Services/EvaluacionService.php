<?php

namespace App\Services;

use App\Models\Evaluacion;
use App\Models\PerfilTutor;
use App\Models\Tutoria;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EvaluacionService
{
    public function __construct(private BitacoraService $bitacora) {}

    public function calificar(User $estudiante, Tutoria $tutoria, int $puntuacion, ?string $comentario): Evaluacion
    {
        return DB::transaction(function () use ($estudiante, $tutoria, $puntuacion, $comentario): Evaluacion {
            $tutoria = Tutoria::whereKey($tutoria->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($estudiante)->authorize('calificar', $tutoria);
            abort_unless($tutoria->estado === 'COMPLETADA', 409, 'Solo puedes calificar una tutoría que se realizó.');
            abort_if(Evaluacion::where('tutoria_id', $tutoria->id)->exists(), 409, 'Ya calificaste esta tutoría.');

            $evaluacion = new Evaluacion([
                'tutoria_id' => $tutoria->id,
                'estudiante_id' => $tutoria->estudiante_id,
                'tutor_id' => $tutoria->tutor_id,
                'puntuacion' => $puntuacion,
                'comentario' => $comentario,
            ]);

            try {
                $evaluacion->save();
            } catch (UniqueConstraintViolationException) {
                abort(409, 'Ya calificaste esta tutoría.');
            }

            $this->recalcularPromedio($tutoria->tutor_id);
            $this->bitacora->registrar(
                $estudiante,
                'TUTORIA_CALIFICADA',
                'evaluaciones',
                $evaluacion->id,
                "Calificó la tutoría #{$tutoria->id} con {$puntuacion}."
            );

            return $evaluacion;
        }, 3);
    }

    private function recalcularPromedio(int $tutorId): void
    {
        $perfil = PerfilTutor::where('user_id', $tutorId)->lockForUpdate()->first();
        if (! $perfil) {
            return;
        }

        $evaluaciones = Evaluacion::where('tutor_id', $tutorId);
        $perfil->forceFill([
            'total_calificaciones' => (clone $evaluaciones)->count(),
            'promedio_calificacion' => round((float) $evaluaciones->avg('puntuacion'), 2),
        ])->save();
    }
}
