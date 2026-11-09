<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        // Ejemplos: ajústalos a las carreras reales
        $carreras = [
            ['nombre' => 'Ingeniería en Ciencias de la Computación', 'facultad' => 'Ingeniería'],
            ['nombre' => 'Ingeniería Industrial', 'facultad' => 'Ingeniería'],
            ['nombre' => 'Administración de Empresas', 'facultad' => 'Ciencias Económicas y Administrativas'],
        ];

        foreach ($carreras as $carrera) {
            Carrera::firstOrCreate(
                ['nombre' => $carrera['nombre']],
                ['facultad' => $carrera['facultad']]
            );
        }
    }
}