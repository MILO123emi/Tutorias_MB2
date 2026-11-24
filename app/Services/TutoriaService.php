<?php

namespace App\Services;

use App\Mail\TutoriaReservadaMail;
use App\Models\Tutoria;
use App\Models\TutoriaNoRealizada;
use App\Models\User;
use App\Notifications\InasistenciaReportada;
use App\Notifications\TutoriaCancelada;
use App\Notifications\TutoriaReservada;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TutoriaService
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private ParametroService $parametros,
        private BitacoraService $bitacora,
    ) {}

    public function cancelar(User $actor, Tutoria $tutoria, string $motivo): Tutoria
    {
        return DB::transaction(function () use ($actor, $tutoria, $motivo): Tutoria {
            $tutoria = Tutoria::whereKey($tutoria->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('cancel', $tutoria);
            abort_unless($tutoria->estado === 'CONFIRMADA', 409, 'La tutoría ya no está confirmada.');
            $inicio = CarbonImmutable::parse($tutoria->fecha->format('Y-m-d'), config('app.timezone'))
                ->setTime($tutoria->hora_inicio, 0);
            if (! $this->disponibilidad->cumpleAnticipacion($inicio)) {
                throw ValidationException::withMessages(['tutoria' => 'La tutoría no cumple la anticipación mínima para cancelar.']);
            }
            $tutoria->estado = $actor->id === $tutoria->estudiante_id ? 'CANCELADA_ESTUDIANTE' : 'CANCELADA_TUTOR';
            $tutoria->save();
            TutoriaNoRealizada::create([
                'tutoria_id' => $tutoria->id,
                'tipo' => $tutoria->estado,
                'reportado_por' => $actor->id,
                'motivo_para_admin' => $motivo,
                'motivo_para_contraparte' => null,
            ]);
            $this->bitacora->registrar($actor, 'TUTORIA_CANCELADA', 'tutorias', $tutoria->id);
            $tutoria->load(['materia', 'tutor', 'estudiante', 'evaluacion']);
            $contraparte = $actor->id === $tutoria->estudiante_id ? $tutoria->tutor : $tutoria->estudiante;
            $contraparte->notify(new TutoriaCancelada($tutoria));

            return $tutoria;
        }, 3);
    }

    // El tutor confirma desde que empieza la tutoría hasta que vence su plazo.
    public function confirmarAsistencia(User $tutor, Tutoria $tutoria): Tutoria
    {
        return DB::transaction(function () use ($tutor, $tutoria): Tutoria {
            $tutoria = Tutoria::whereKey($tutoria->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($tutor)->authorize('confirmarAsistencia', $tutoria);
            abort_unless($tutoria->estado === 'CONFIRMADA', 409, 'La tutoría ya no está confirmada.');
            $this->validarVentana($tutoria, $this->parametros->entero('horas_para_confirmar', 24), 'confirmar la asistencia');

            $tutoria->estado = 'COMPLETADA';
            $tutoria->asistencia_confirmada_at = now();
            $tutoria->save();
            $this->bitacora->registrar($tutor, 'ASISTENCIA_CONFIRMADA', 'tutorias', $tutoria->id);

            return $tutoria->load(['materia', 'tutor', 'estudiante', 'evaluacion']);
        }, 3);
    }

    // Quien reporta deja constancia de que faltó la otra parte. El motivo solo lo ve administración.
    public function reportarInasistencia(User $actor, Tutoria $tutoria, string $motivo): Tutoria
    {
        return DB::transaction(function () use ($actor, $tutoria, $motivo): Tutoria {
            $tutoria = Tutoria::whereKey($tutoria->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('reportarInasistencia', $tutoria);
            abort_unless($tutoria->estado === 'CONFIRMADA', 409, 'La tutoría ya no está confirmada.');
            $this->validarVentana($tutoria, $this->parametros->entero('horas_para_sin_confirmar', 48), 'reportar la inasistencia');

            $tutoria->estado = $actor->id === $tutoria->estudiante_id ? 'NO_ASISTIO_TUTOR' : 'NO_ASISTIO_ESTUDIANTE';
            $tutoria->save();
            TutoriaNoRealizada::create([
                'tutoria_id' => $tutoria->id,
                'tipo' => $tutoria->estado,
                'reportado_por' => $actor->id,
                'motivo_para_admin' => $motivo,
                'motivo_para_contraparte' => null,
            ]);
            $this->bitacora->registrar($actor, 'INASISTENCIA_REPORTADA', 'tutorias', $tutoria->id, requiereAtencion: true);
            $tutoria->load(['materia', 'tutor', 'estudiante', 'evaluacion']);
            $contraparte = $actor->id === $tutoria->estudiante_id ? $tutoria->tutor : $tutoria->estudiante;
            $contraparte->notify(new InasistenciaReportada($tutoria));

            return $tutoria;
        }, 3);
    }

    // Lo usa el comando programado: cierra lo que nadie confirmó ni reportó dentro del plazo.
    public function cerrarVencidas(): int
    {
        $ahora = CarbonImmutable::now(config('app.timezone'));
        $horas = $this->parametros->entero('horas_para_sin_confirmar', 48);
        $cerradas = 0;

        Tutoria::where('estado', 'CONFIRMADA')->whereDate('fecha', '<=', $ahora->toDateString())
            ->select(['id', 'fecha', 'hora_inicio'])
            ->chunkById(100, function ($tutorias) use ($ahora, $horas, &$cerradas): void {
                foreach ($tutorias as $tutoria) {
                    if ($ahora->lte($this->inicioDe($tutoria)->addHour()->addHours($horas))) {
                        continue;
                    }
                    $cerradas += DB::transaction(function () use ($tutoria): int {
                        // Se vuelve a leer por si alguien confirmó mientras corría el comando.
                        $actual = Tutoria::whereKey($tutoria->id)->lockForUpdate()->first();
                        if ($actual?->estado !== 'CONFIRMADA') {
                            return 0;
                        }
                        $actual->estado = 'SIN_CONFIRMAR';
                        $actual->save();
                        TutoriaNoRealizada::create(['tutoria_id' => $actual->id, 'tipo' => 'SIN_CONFIRMAR', 'reportado_por' => null]);
                        $this->bitacora->registrar(null, 'TUTORIA_SIN_CONFIRMAR', 'tutorias', $actual->id, requiereAtencion: true);

                        return 1;
                    }, 3);
                }
            });

        return $cerradas;
    }

    private function inicioDe(Tutoria $tutoria): CarbonImmutable
    {
        return CarbonImmutable::parse($tutoria->fecha->format('Y-m-d'), config('app.timezone'))
            ->setTime($tutoria->hora_inicio, 0);
    }

    // La ventana abre al empezar la tutoría y cierra las horas indicadas después de terminar.
    private function validarVentana(Tutoria $tutoria, int $horas, string $accion): void
    {
        $ahora = CarbonImmutable::now(config('app.timezone'));
        $inicio = $this->inicioDe($tutoria);

        if ($ahora->lt($inicio)) {
            throw ValidationException::withMessages(['tutoria' => "Podrás {$accion} cuando empiece la tutoría."]);
        }
        if ($ahora->gt($inicio->addHour()->addHours($horas))) {
            throw ValidationException::withMessages(['tutoria' => "Ya pasó el plazo para {$accion}."]);
        }
    }

    /**
     * @param  array{tutor_id: int, fecha: string, hora_inicio: int, materia_id: int, tema: string, descripcion?: ?string}  $datos
     */
    public function reservar(User $estudiante, array $datos): Tutoria
    {
        Gate::forUser($estudiante)->authorize('create', Tutoria::class);

        return DB::transaction(function () use ($estudiante, $datos): Tutoria {
            // El orden de las filas evita interbloqueos cuando dos tutores se reservan entre sí.
            $participantes = User::whereIn('id', [$estudiante->id, $datos['tutor_id']])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $estudiante = $participantes->get($estudiante->id);
            abort_unless($estudiante?->activo, 403, 'Tu cuenta está suspendida.');
            Gate::forUser($estudiante)->authorize('create', Tutoria::class);
            $tutor = $participantes->get($datos['tutor_id']);

            if ($tutor?->id === $estudiante->id) {
                throw ValidationException::withMessages(['tutor_id' => 'No puedes reservar una tutoría contigo mismo.']);
            }
            if (! $tutor?->activo || ! $tutor->esTutorAprobado()) {
                throw ValidationException::withMessages(['tutor_id' => 'El tutor no está disponible.']);
            }

            $dia = $this->disponibilidad->validarFecha($datos['fecha']);
            $hora = (int) $datos['hora_inicio'];
            if ($hora < 7 || $hora > 20 || ! $this->disponibilidad->cumpleAnticipacion($dia->setTime($hora, 0))) {
                throw ValidationException::withMessages(['hora_inicio' => 'El bloque ya pasó o no cumple la anticipación mínima.']);
            }

            $bloque = $tutor->disponibilidad()->where('dia_semana', $dia->isoWeekday())
                ->where('hora_inicio', $hora)->lockForUpdate()->first();
            if (! $bloque) {
                throw ValidationException::withMessages(['hora_inicio' => 'El tutor no ha publicado ese bloque.']);
            }
            if (! $tutor->materias()->whereKey($datos['materia_id'])->exists()) {
                throw ValidationException::withMessages(['materia_id' => 'El tutor no imparte esa materia.']);
            }

            $reservas = Tutoria::where('tutor_id', $tutor->id)->where('fecha', $datos['fecha'])
                ->whereNotIn('estado', ['CANCELADA_TUTOR', 'CANCELADA_ESTUDIANTE'])
                ->lockForUpdate()->get(['id', 'hora_inicio', 'estado']);
            if ($reservas->whereIn('estado', ['CONFIRMADA', 'COMPLETADA'])->contains('hora_inicio', $hora)) {
                abort(409, 'Ese bloque ya fue reservado.');
            }
            if (Tutoria::where('estudiante_id', $estudiante->id)->where('fecha', $datos['fecha'])
                ->where('hora_inicio', $hora)->ocupanBloque()->lockForUpdate()->first(['id'])) {
                throw ValidationException::withMessages(['hora_inicio' => 'Ya tienes una tutoría a esa hora.']);
            }
            $limite = $this->parametros->entero('max_tutorias_por_dia');
            if ($limite !== null && $reservas->count() >= $limite) {
                throw ValidationException::withMessages(['tutor_id' => 'El tutor ya no tiene cupo ese día.']);
            }

            $tutoria = new Tutoria(Arr::only($datos, ['fecha', 'hora_inicio', 'materia_id', 'tema', 'descripcion']));
            $tutoria->estudiante_id = $estudiante->id;
            $tutoria->tutor_id = $tutor->id;
            $tutoria->modalidad = $bloque->modalidad;
            $tutoria->lugar_o_enlace = $this->disponibilidad->lugarDelBloque($tutor, $bloque);
            $tutoria->estado = 'CONFIRMADA';
            try {
                $tutoria->save();
            } catch (UniqueConstraintViolationException) {
                abort(409, 'Ese bloque ya fue reservado.');
            }

            $tutoria->load(['materia', 'tutor', 'estudiante', 'evaluacion']);
            $this->bitacora->registrar($estudiante, 'TUTORIA_RESERVADA', 'tutorias', $tutoria->id);
            $tutor->notify(new TutoriaReservada($tutoria));
            Mail::to($estudiante)->queue((new TutoriaReservadaMail($tutoria))->afterCommit());

            return $tutoria;
        }, 3);
    }
}
