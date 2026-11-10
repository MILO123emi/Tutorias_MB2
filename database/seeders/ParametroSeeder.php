<?php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;

class ParametroSeeder extends Seeder
{
    public function run(): void
    {
        // Valores provisionales (pendiente 15 del análisis). La admin los cambia desde la web.
        $parametros = [
            'max_tutorias_por_dia' => '4',                  // tope de tutorías por día por tutor; vacío = sin límite (RN-05)
            'anticipacion_min_minutos' => '60',             // RN-16
            'horas_para_confirmar' => '24',                 // ventana para confirmar asistencia (9.2)
            'horas_para_sin_confirmar' => '48',             // después pasa a SIN_CONFIRMAR (9.1)
        ];

        foreach ($parametros as $clave => $valor) {
            Parametro::firstOrCreate(['clave' => $clave], ['valor' => $valor]);
        }
    }
}