<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('registra el token FCM del dispositivo', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $this->postJson('/api/v1/dispositivos', ['fcm_token' => 'token-abc'])
        ->assertStatus(201);

    $this->assertDatabaseHas('dispositivos', [
        'user_id' => $usuario->id,
        'fcm_token' => 'token-abc',
    ]);
});

test('borra el token FCM del dispositivo', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);
    $this->postJson('/api/v1/dispositivos', ['fcm_token' => 'token-abc'])->assertStatus(201);

    $this->deleteJson('/api/v1/dispositivos', ['fcm_token' => 'token-abc'])->assertOk();

    $this->assertDatabaseMissing('dispositivos', ['fcm_token' => 'token-abc']);
});

test('el logout borra el token FCM del celular', function () {
    $usuario = User::factory()->create();
    $token = $usuario->createToken('app')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/dispositivos', ['fcm_token' => 'token-abc'])
        ->assertStatus(201);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout', ['fcm_token' => 'token-abc'])
        ->assertOk();

    $this->assertDatabaseMissing('dispositivos', ['fcm_token' => 'token-abc']);
});
