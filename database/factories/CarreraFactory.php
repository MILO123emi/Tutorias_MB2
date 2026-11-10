<?php

namespace Database\Factories;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrera>
 */
class CarreraFactory extends Factory
{
    protected $model = Carrera::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Carrera '.fake()->unique()->numberBetween(1, 99999),
            'facultad' => fake()->randomElement([
                'Ingeniería',
                'Ciencias Económicas y Administrativas',
                'Ciencias de la Salud',
            ]),
        ];
    }
}
