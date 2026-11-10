<?php

use App\Models\CodigoVerificacion;
use App\Models\User;
use Database\Factories\CarreraFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function registroConTokenValido(string $correo, string $token): void
{
    CodigoVerificacion::create([
        'correo' => $correo,
        'tipo' => 'VERIFICAR_CORREO',
        'codigo_hash' => Hash::make('123456'),
        'intentos' => 0,
        'expira_at' => now()->addMinutes(15),
        'usado_at' => now(),
        'ultimo_envio_at' => now(),
        'token_registro_hash' => hash('sha256', $token),
        'token_registro_expira_at' => now()->addMinutes(30),
    ]);
}

function datosDeRegistro(string $token, int $carreraId): array
{
    return [
        'registro_token' => $token,
        'name' => 'Juan Pérez',
        'carrera_id' => $carreraId,
        'telefono' => '99998888',
        'password' => 'ClaveNueva123',
        'password_confirmation' => 'ClaveNueva123',
    ];
}

test('crea un estudiante verificado y devuelve un token', function () {
    $carrera = CarreraFactory::new()->create();
    $token = Str::random(64);
    registroConTokenValido('nuevo@unicah.edu', $token);

    $this->postJson('/api/v1/auth/registro/completar', datosDeRegistro($token, $carrera->id))
        ->assertCreated()
        ->assertJsonStructure(['token', 'usuario' => ['id', 'email', 'rol']]);

    $usuario = User::where('email', 'nuevo@unicah.edu')->first();
    expect($usuario)->not->toBeNull();
    expect($usuario->rol)->toBe('ESTUDIANTE');
    expect($usuario->email_verified_at)->not->toBeNull();
});

test('ignora el rol enviado y siempre crea un estudiante', function () {
    $carrera = CarreraFactory::new()->create();
    $token = Str::random(64);
    registroConTokenValido('nuevo@unicah.edu', $token);

    $this->postJson(
        '/api/v1/auth/registro/completar',
        datosDeRegistro($token, $carrera->id) + ['rol' => 'ADMIN'],
    )->assertCreated();

    expect(User::where('email', 'nuevo@unicah.edu')->first()->rol)->toBe('ESTUDIANTE');
});

test('rechaza un registro_token inválido', function () {
    $carrera = CarreraFactory::new()->create();

    $this->postJson('/api/v1/auth/registro/completar', datosDeRegistro('token-inexistente', $carrera->id))
        ->assertStatus(422)->assertJsonValidationErrors('registro_token');
});

test('el registro_token sirve una sola vez', function () {
    $carrera = CarreraFactory::new()->create();
    $token = Str::random(64);
    registroConTokenValido('nuevo@unicah.edu', $token);

    $this->postJson('/api/v1/auth/registro/completar', datosDeRegistro($token, $carrera->id))
        ->assertCreated();

    $this->postJson('/api/v1/auth/registro/completar', datosDeRegistro($token, $carrera->id))
        ->assertStatus(422)->assertJsonValidationErrors('registro_token');
});
