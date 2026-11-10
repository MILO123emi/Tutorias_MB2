<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function crearNotificacion(User $user): string
{
    $id = (string) Str::uuid();
    $user->notifications()->create([
        'id' => $id,
        'type' => 'App\\Notifications\\Prueba',
        'data' => ['titulo' => 'Hola'],
    ]);

    return $id;
}

test('lista las notificaciones y el contador de no leídas', function () {
    $usuario = User::factory()->create();
    crearNotificacion($usuario);
    crearNotificacion($usuario);
    Sanctum::actingAs($usuario);

    $this->getJson('/api/v1/notificaciones')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/notificaciones/no-leidas')->assertOk()->assertJsonPath('no_leidas', 2);
});

test('marca una notificación propia como leída', function () {
    $usuario = User::factory()->create();
    $id = crearNotificacion($usuario);
    Sanctum::actingAs($usuario);

    $this->postJson("/api/v1/notificaciones/{$id}/leida")->assertOk();
    $this->getJson('/api/v1/notificaciones/no-leidas')->assertJsonPath('no_leidas', 0);
});

test('no puede marcar la notificación de otra persona', function () {
    $otro = User::factory()->create();
    $idAjena = crearNotificacion($otro);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/notificaciones/{$idAjena}/leida")->assertStatus(404);
});

test('marca todas como leídas', function () {
    $usuario = User::factory()->create();
    crearNotificacion($usuario);
    crearNotificacion($usuario);
    Sanctum::actingAs($usuario);

    $this->postJson('/api/v1/notificaciones/leer-todas')->assertOk();
    $this->getJson('/api/v1/notificaciones/no-leidas')->assertJsonPath('no_leidas', 0);
});
