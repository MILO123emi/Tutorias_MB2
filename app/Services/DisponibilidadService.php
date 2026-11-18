<?php

namespace App\Services;

use App\Models\Disponibilidad;
use App\Models\Tutoria;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisponibilidadService
{
    public function __construct(private ParametroService $parametros) {}

    /** @return Collection<int, Disponibilidad> */
    public function obtener(User $tutor): Collection
    {
        return $tutor->disponibilidad()->orderBy('dia_semana')->orderBy('hora_inicio')->get();
    }

    /**
     * @param  array<int, array{dia_semana: int, hora_inicio: int, modalidad: string, lugar_o_enlace?: ?string}>  $bloques
     * @return Collection<int, Disponibilidad>
     */
    public function reemplazar(User $tutor, array $bloques): Collection
    {
        return DB::transaction(function () use ($tutor, $bloques): Collection {
            User::whereKey($tutor->id)->lockForUpdate()->firstOrFail();
            $tutor->disponibilidad()->delete();
            $tutor->disponibilidad()->createMany($bloques);

            return $this->obtener($tutor);
        }, 3);
    }

    public function validarFecha(string $fecha): CarbonImmutable
    {
        $dia = CarbonImmutable::createFromFormat('!Y-m-d', $fecha, config('app.timezone'));
        $hoy = CarbonImmutable::today(config('app.timezone'));

        if ($dia->lt($hoy) || $dia->gt($hoy->addDays(7))) {
            throw ValidationException::withMessages(['fecha' => 'La fecha debe estar entre hoy y los próximos 7 días.']);
        }
        if ($dia->isSunday()) {
            throw ValidationException::withMessages(['fecha' => 'No hay tutorías los domingos.']);
        }

        return $dia;
    }

    public function cumpleAnticipacion(CarbonImmutable $inicio): bool
    {
        $ahora = CarbonImmutable::now(config('app.timezone'));
        $minutos = max(0, $this->parametros->entero('anticipacion_min_minutos', 60));

        return $inicio->gt($ahora) && $inicio->gte($ahora->addMinutes($minutos));
    }

    public function lugarDelBloque(User $tutor, Disponibilidad $bloque): ?string
    {
        return $bloque->lugar_o_enlace ?: ($bloque->modalidad === 'VIRTUAL'
            ? $tutor->perfilTutor?->enlace_virtual_default
            : $tutor->perfilTutor?->lugar_presencial_default);
    }

    /** @return BaseCollection<int, array<string, mixed>> */
    public function bloquesDelDia(User $tutor, string $fecha): BaseCollection
    {
        $dia = $this->validarFecha($fecha);
        $tutorias = Tutoria::where('tutor_id', $tutor->id)->where('fecha', $fecha)->get(['hora_inicio', 'estado']);
        $ocupadas = $tutorias->whereIn('estado', ['CONFIRMADA', 'COMPLETADA'])->pluck('hora_inicio');
        $limite = $this->parametros->entero('max_tutorias_por_dia');
        $sinCupo = $limite !== null
            && $tutorias->whereNotIn('estado', ['CANCELADA_TUTOR', 'CANCELADA_ESTUDIANTE'])->count() >= $limite;

        return $tutor->disponibilidad()
            ->where('dia_semana', $dia->isoWeekday())
            ->whereBetween('hora_inicio', [7, 20])
            ->orderBy('hora_inicio')->get()
            ->filter(fn (Disponibilidad $bloque) => $this->cumpleAnticipacion($dia->setTime($bloque->hora_inicio, 0)))
            ->map(function (Disponibilidad $bloque) use ($tutor, $ocupadas, $sinCupo): array {
                $ocupado = $ocupadas->contains($bloque->hora_inicio);

                return [
                    'hora_inicio' => $bloque->hora_inicio,
                    'hora_fin' => $bloque->hora_inicio + 1,
                    'modalidad' => $bloque->modalidad,
                    'lugar_o_enlace' => $this->lugarDelBloque($tutor, $bloque),
                    'estado' => $ocupado || $sinCupo ? 'ocupado' : 'libre',
                    'disponible' => ! $ocupado && ! $sinCupo,
                    'motivo' => $ocupado ? 'bloque_reservado' : ($sinCupo ? 'cupo_del_dia_lleno' : null),
                ];
            })->values();
    }
}
