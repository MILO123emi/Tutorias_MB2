<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Database\Seeder;

class EstudiantePruebaSeeder extends Seeder
{
    public function run(): void
    {
        // Cuenta solo para pruebas en desarrollo; nunca se crea en producción.
        if (! app()->environment('local')) {
            return;
        }

        $email = env('ESTUDIANTE_PRUEBA_EMAIL', 'estudiante@unicah.edu');
        $password = env('ESTUDIANTE_PRUEBA_PASSWORD');

        if (! $password) {
            $this->command->warn('Falta ESTUDIANTE_PRUEBA_PASSWORD en el .env. No se creó el estudiante de prueba.');
            return;
        }

        $estudiante = User::firstOrNew(['email' => $email]);

        // La contraseña solo se asigna al crear la cuenta, para no pisarla si el seeder se vuelve a correr.
        if (! $estudiante->exists) {
            $estudiante->password = $password; // el modelo la guarda con hash
        }

        // 'rol' y 'activo' no están en $fillable a propósito, por eso se asignan directo.
        $estudiante->name = 'Estudiante de Prueba';
        $estudiante->rol = 'ESTUDIANTE';
        $estudiante->activo = true;
        $estudiante->carrera_id = $estudiante->carrera_id ?? Carrera::value('id');
        $estudiante->telefono = $estudiante->telefono ?? '0000-0000';
        $estudiante->email_verified_at = $estudiante->email_verified_at ?? now();
        $estudiante->save();
    }
}
