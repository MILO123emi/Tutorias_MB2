<?php

namespace App\Console\Commands;

use App\Services\TutoriaService;
use Illuminate\Console\Command;

// Corre cada hora: las tutorías que nadie confirmó ni reportó a tiempo pasan a SIN_CONFIRMAR.
class CerrarTutoriasVencidas extends Command
{
    protected $signature = 'tutorias:cerrar-vencidas';

    protected $description = 'Marca como SIN_CONFIRMAR las tutorías vencidas sin asistencia ni reporte';

    public function handle(TutoriaService $servicio): int
    {
        $cerradas = $servicio->cerrarVencidas();
        $this->info("Tutorías cerradas: {$cerradas}");

        return self::SUCCESS;
    }
}
