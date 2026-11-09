<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    public function run(): void
    {
        // Ejemplos: carrera => lista de materias
        $catalogo = [
            'Ingeniería en Ciencias de la Computación' => ['Cálculo I', 'Programación I', 'Base de Datos I'],
            'Ingeniería Industrial' => ['Cálculo I', 'Física I', 'Estadística I'],
            'Administración de Empresas' => ['Contabilidad I', 'Matemática Financiera', 'Microeconomía'],
        ];

        foreach ($catalogo as $nombreCarrera => $materias) {
            $carrera = Carrera::where('nombre', $nombreCarrera)->firstOrFail();

            foreach ($materias as $nombreMateria) {
                Materia::firstOrCreate([
                    'nombre' => $nombreMateria,
                    'carrera_id' => $carrera->id,
                ]);
            }
        }
    }
}