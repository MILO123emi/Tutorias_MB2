<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = [
            ['nombre' => 'Administración de Empresas', 'facultad' => 'Ciencias Económicas y Administrativas'],
            ['nombre' => 'Finanzas', 'facultad' => 'Ciencias Económicas y Administrativas'],
            ['nombre' => 'Arquitectura', 'facultad' => 'Ingeniería'],
            ['nombre' => 'Ingeniería Civil', 'facultad' => 'Ingeniería'],
            ['nombre' => 'Ingeniería en Ciencias de la Computación', 'facultad' => 'Ingeniería'],
            ['nombre' => 'Ingeniería Industrial', 'facultad' => 'Ingeniería'],
            ['nombre' => 'Derecho', 'facultad' => 'Ciencias Jurídicas'],
            ['nombre' => 'Enfermería', 'facultad' => 'Ciencias de la Salud'],
            ['nombre' => 'Psicología', 'facultad' => 'Ciencias Sociales'],
        ];

        foreach ($carreras as $carrera) {
            Carrera::firstOrCreate(
                ['nombre' => $carrera['nombre']],
                ['facultad' => $carrera['facultad']]
            );
        }
    }
}
