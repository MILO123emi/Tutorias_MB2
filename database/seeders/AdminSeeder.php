<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@unicah.edu');
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $this->command->error('Falta ADMIN_PASSWORD en el .env. No se creó la cuenta admin.');
            return;
        }

        $admin = User::firstOrNew(['email' => $email]);

        // La contraseña solo se asigna al crear la cuenta, para no pisarla si el seeder se vuelve a correr.
        if (! $admin->exists) {
            $admin->password = $password; // el modelo la guarda con hash
        }

        // 'rol' y 'activo' no están en $fillable a propósito, por eso se asignan directo.
        $admin->name = 'Bienestar Estudiantil';
        $admin->rol = 'ADMIN';
        $admin->activo = true;
        $admin->email_verified_at = $admin->email_verified_at ?? now();
        $admin->save();
    }
}