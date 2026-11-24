<?php

namespace Database\Factories;

use App\Models\Evaluacion;
use App\Models\Tutoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluacion>
 */
class EvaluacionFactory extends Factory
{
    protected $model = Evaluacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tutoria_id' => Tutoria::factory()->estado('COMPLETADA'),
            'estudiante_id' => fn (array $attributes) => Tutoria::query()->whereKey($attributes['tutoria_id'])->value('estudiante_id'),
            'tutor_id' => fn (array $attributes) => Tutoria::query()->whereKey($attributes['tutoria_id'])->value('tutor_id'),
            'puntuacion' => fake()->numberBetween(1, 5),
            'comentario' => fake()->optional()->sentence(),
        ];
    }
}
