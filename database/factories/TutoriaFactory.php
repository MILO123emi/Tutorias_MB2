<?php

namespace Database\Factories;

use App\Models\Materia;
use App\Models\Tutoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tutoria>
 */
class TutoriaFactory extends Factory
{
    protected $model = Tutoria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estudiante_id' => User::factory(),
            'tutor_id' => User::factory(),
            'materia_id' => Materia::factory(),
            'tema' => fake()->sentence(4),
            'descripcion' => fake()->sentence(10),
            'fecha' => now()->addDays(fake()->numberBetween(1, 7))->toDateString(),
            'hora_inicio' => fake()->numberBetween(7, 20),
            'modalidad' => fake()->randomElement(['PRESENCIAL', 'VIRTUAL']),
            'estado' => 'CONFIRMADA',
        ];
    }

    public function estado(string $estado): static
    {
        return $this->state(fn (array $attributes) => ['estado' => $estado]);
    }
}
