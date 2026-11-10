<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // El orden importa: las materias necesitan que ya existan las carreras.
        $this->call([
            CarreraSeeder::class,
            MateriaSeeder::class,
            ParametroSeeder::class,
            AdminSeeder::class,
            EstudiantePruebaSeeder::class,
        ]);
    }
}
