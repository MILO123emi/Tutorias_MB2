<?php

namespace Database\Factories;

use App\Models\Citacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citacion>
 */
class CitacionFactory extends Factory
{
    protected $model = Citacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->admin(),
            'user_id' => User::factory(),
            'mensaje' => fake()->sentence(),
        ];
    }
}
