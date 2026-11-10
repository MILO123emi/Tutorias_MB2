<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\User;
use App\Models\Parametro;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $carrera = Carrera::firstOrCreate(
            ['nombre' => 'Ingeniería en Ciencias de la Computación'],
            ['facultad' => 'Facultad de Ingeniería']
        );

        foreach (['Programación I', 'Estructuras de Datos', 'Bases de Datos I', 'Ingeniería de Software'] as $mat) {
            Materia::firstOrCreate(['nombre' => $mat, 'carrera_id' => $carrera->id]);
        }

        User::firstOrCreate(
            ['email' => 'admin@unicah.edu'],
            [
                'name' => 'Lic. Kerry (Admin)',
                'password' => Hash::make('password123'),
                'rol' => 'ADMIN',
                'carrera_id' => null,
                'telefono' => '9999-9999',
                'foto_url' => 'https://ui-avatars.com/api/?name=Admin+Bienestar',
                'email_verified_at' => now(),
                'activo' => true,
            ]
        );

        $parametros = [
            'max_tutorias_por_dia' => '4',
            'anticipacion_min_minutos' => '60',
            'horas_para_confirmar' => '24',
            'horas_para_sin_confirmar' => '24',
            'max_bloques_estudiante_tutor_dia' => '2',
        ];
        foreach ($parametros as $clave => $valor) {
            Parametro::firstOrCreate(['clave' => $clave], ['valor' => $valor]);
        }
    }
}
