<?php

use App\Models\User;

test('inicia sesión con credenciales correctas y devuelve un token', function () {
    User::factory()->create(['email' => 'estudiante@unicah.edu']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'estudiante@unicah.edu',
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token', 'usuario' => ['id', 'rol']]);
});

test('rechaza credenciales incorrectas', function () {
    User::factory()->create(['email' => 'estudiante@unicah.edu']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'estudiante@unicah.edu',
        'password' => 'equivocada',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('bloquea con 403 a una cuenta suspendida', function () {
    User::factory()->suspendido()->create(['email' => 'suspendido@unicah.edu']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'suspendido@unicah.edu',
        'password' => 'password',
    ])->assertStatus(403);
});

test('cierra la sesión y borra el token usado', function () {
    $usuario = User::factory()->create();
    $token = $usuario->createToken('app')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($usuario->fresh()->tokens()->count())->toBe(0);
});
