<?php

use App\Mail\CodigoVerificacionMail;
use App\Models\CodigoVerificacion;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('envía un código a un correo institucional válido', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/registro/enviar-codigo', [
        'correo' => 'nuevo@unicah.edu',
    ])->assertOk();

    $this->assertDatabaseHas('codigos_verificacion', [
        'correo' => 'nuevo@unicah.edu',
        'tipo' => 'VERIFICAR_CORREO',
    ]);
    Mail::assertQueued(CodigoVerificacionMail::class);
});

test('rechaza un correo de otro dominio', function () {
    $this->postJson('/api/v1/auth/registro/enviar-codigo', [
        'correo' => 'persona@gmail.com',
    ])->assertStatus(422)->assertJsonValidationErrors('correo');
});

test('rechaza un correo que ya tiene cuenta', function () {
    User::factory()->create(['email' => 'repetido@unicah.edu']);

    $this->postJson('/api/v1/auth/registro/enviar-codigo', [
        'correo' => 'repetido@unicah.edu',
    ])->assertStatus(422)->assertJsonValidationErrors('correo');
});

test('no deja reenviar el código antes de 60 segundos', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/registro/enviar-codigo', ['correo' => 'nuevo@unicah.edu'])->assertOk();

    $this->postJson('/api/v1/auth/registro/enviar-codigo', ['correo' => 'nuevo@unicah.edu'])
        ->assertStatus(429)
        ->assertJsonStructure(['message', 'reintentar_en']);
});

test('verifica el código correcto y devuelve un registro_token', function () {
    CodigoVerificacion::create([
        'correo' => 'nuevo@unicah.edu',
        'tipo' => 'VERIFICAR_CORREO',
        'codigo_hash' => Hash::make('123456'),
        'intentos' => 0,
        'expira_at' => now()->addMinutes(15),
        'ultimo_envio_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/registro/verificar-codigo', [
        'correo' => 'nuevo@unicah.edu',
        'codigo' => '123456',
    ])->assertOk()->assertJsonStructure(['registro_token']);
});

test('suma un intento con un código incorrecto', function () {
    CodigoVerificacion::create([
        'correo' => 'nuevo@unicah.edu',
        'tipo' => 'VERIFICAR_CORREO',
        'codigo_hash' => Hash::make('123456'),
        'intentos' => 0,
        'expira_at' => now()->addMinutes(15),
        'ultimo_envio_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/registro/verificar-codigo', [
        'correo' => 'nuevo@unicah.edu',
        'codigo' => '000000',
    ])->assertStatus(422)->assertJsonValidationErrors('codigo');

    $this->assertDatabaseHas('codigos_verificacion', [
        'correo' => 'nuevo@unicah.edu',
        'intentos' => 1,
    ]);
});
