<?php

use App\Models\Citacion;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('lista las citaciones dirigidas al usuario', function () {
    $usuario = User::factory()->create();
    Citacion::factory()->count(2)->create(['user_id' => $usuario->id]);
    Citacion::factory()->create(); // de otra persona
    Sanctum::actingAs($usuario);

    $this->getJson('/api/v1/citaciones')->assertOk()->assertJsonCount(2, 'data');
});

test('marca una citación propia como enterada', function () {
    $usuario = User::factory()->create();
    $citacion = Citacion::factory()->create(['user_id' => $usuario->id]);
    Sanctum::actingAs($usuario);

    $this->postJson("/api/v1/citaciones/{$citacion->id}/enterado")->assertOk();

    expect($citacion->fresh()->enterado_at)->not->toBeNull();
    $this->assertDatabaseHas('bitacora', [
        'tipo_evento' => 'CITACION_ENTERADA',
        'actor_id' => $usuario->id,
    ]);
});

test('no puede marcar enterada una citación ajena', function () {
    $citacionAjena = Citacion::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/citaciones/{$citacionAjena->id}/enterado")->assertStatus(404);
});
