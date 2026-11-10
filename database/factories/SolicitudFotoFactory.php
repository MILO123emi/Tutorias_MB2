<?php

namespace Database\Factories;

use App\Models\SolicitudFoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudFoto>
 */
class SolicitudFotoFactory extends Factory
{
    protected $model = SolicitudFoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'foto_nueva_url' => '/storage/fotos/'.fake()->uuid().'.jpg',
        ];
    }
}
