<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\PerfilTutor;
use App\Models\SolicitudFoto;
use App\Models\Tutoria;
use App\Models\TutoriaNoRealizada;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class AdminResumenService
{
    /** @return array{fecha: string, contadores: array<string, int>, tutorias_hoy: Collection<int, Tutoria>} */
    public function resumen(): array
    {
        $fecha = CarbonImmutable::now(config('app.timezone'))->toDateString();
        $tutorias = Tutoria::whereDate('fecha', $fecha)
            ->with(['materia', 'tutor', 'estudiante'])
            ->orderBy('hora_inicio')->orderBy('id')->get();
        $postulaciones = PerfilTutor::where('estado', 'PENDIENTE')->count();
        $fotos = SolicitudFoto::pendientes()->count();

        return [
            'fecha' => $fecha,
            'contadores' => [
                'tutorias_hoy' => $tutorias->count(),
                'no_realizadas_sin_revisar' => TutoriaNoRealizada::sinRevisar()->count(),
                'eventos_sin_revisar' => Bitacora::pendientesDeAtencion()->count(),
                'postulaciones_pendientes' => $postulaciones,
                'fotos_pendientes' => $fotos,
                'aprobaciones_pendientes' => $postulaciones + $fotos,
            ],
            'tutorias_hoy' => $tutorias,
        ];
    }
}
