<?php

namespace Database\Factories;

use App\Models\PerfilTutor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerfilTutor>
 */
class PerfilTutorFactory extends Factory
{
    protected $model = PerfilTutor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'estado' => 'PENDIENTE',
            'bio' => fake()->sentence(12),
            'total_calificaciones' => 0,
        ];
    }

    public function aprobado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'APROBADO',
            'fecha_aprobacion' => now(),
        ]);
    }

    public function conCalificaciones(float $promedio, int $total): static
    {
        return $this->state(fn (array $attributes) => [
            'promedio_calificacion' => $promedio,
            'total_calificaciones' => $total,
        ]);
    }
}
