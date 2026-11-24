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

    private static int $siguienteTurno = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Recorre los 98 pares de día y hora en orden: si fueran al azar, dos tutorías
        // del mismo estudiante podrían chocar con el índice único y romper una prueba.
        $turno = self::$siguienteTurno++ % 98;

        return [
            'estudiante_id' => User::factory(),
            'tutor_id' => User::factory(),
            'materia_id' => Materia::factory(),
            'tema' => fake()->sentence(4),
            'descripcion' => fake()->sentence(10),
            'fecha' => now()->addDays(1 + intdiv($turno, 14))->toDateString(),
            'hora_inicio' => 7 + $turno % 14,
            'modalidad' => fake()->randomElement(['PRESENCIAL', 'VIRTUAL']),
            'estado' => 'CONFIRMADA',
        ];
    }

    public function estado(string $estado): static
    {
        return $this->state(fn (array $attributes) => ['estado' => $estado]);
    }
}
