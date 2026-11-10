<?php

namespace Database\Factories;

use App\Models\Dispositivo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispositivo>
 */
class DispositivoFactory extends Factory
{
    protected $model = Dispositivo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fcm_token' => 'fcm-'.fake()->unique()->sha256(),
        ];
    }
}
