<?php

namespace Database\Factories;

use App\Models\Disponibilidad;
use App\Models\PerfilTutor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Disponibilidad> */
class DisponibilidadFactory extends Factory
{
    protected $model = Disponibilidad::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tutor_id' => User::factory()->has(PerfilTutor::factory()->aprobado(), 'perfilTutor'),
            'dia_semana' => fake()->numberBetween(1, 6),
            'hora_inicio' => fake()->numberBetween(7, 20),
            'modalidad' => fake()->randomElement(['PRESENCIAL', 'VIRTUAL']),
            'lugar_o_enlace' => null,
        ];
    }
}
