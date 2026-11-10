<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materia>
 */
class MateriaFactory extends Factory
{
    protected $model = Materia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Materia '.fake()->unique()->numberBetween(1, 99999),
            'carrera_id' => Carrera::factory(),
        ];
    }
}
