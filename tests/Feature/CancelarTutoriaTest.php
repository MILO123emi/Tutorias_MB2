<?php

use App\Models\Tutoria;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(8, 0));
});

test('cada participante cancela y deja constancia privada para administración', function (string $participante, string $estado) {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-24', 'hora_inicio' => 14]);
    $actor = $tutoria->$participante;
    $otraParte = $participante === 'estudiante' ? $tutoria->tutor : $tutoria->estudiante;
    Sanctum::actingAs($actor);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => '  Motivo privado  '])
        ->assertOk()->assertJsonPath('data.estado', $estado)->assertDontSee('Motivo privado');
    $this->assertDatabaseHas('tutorias_no_realizadas', [
        'tutoria_id' => $tutoria->id, 'tipo' => $estado, 'reportado_por' => $actor->id,
        'motivo_para_admin' => 'Motivo privado', 'motivo_para_contraparte' => null,
    ]);
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'TUTORIA_CANCELADA', 'actor_id' => $actor->id, 'entidad_id' => $tutoria->id]);
    $notificacion = $otraParte->notifications()->sole();
    expect($notificacion->data['tutoria_id'])->toBe($tutoria->id);
    expect(json_encode($notificacion->data))->not->toContain('Motivo privado');
    expect($actor->notifications()->count())->toBe(0);
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => 'Otra vez'])->assertConflict();
    expect($otraParte->notifications()->count())->toBe(1);
})->with([['estudiante', 'CANCELADA_ESTUDIANTE'], ['tutor', 'CANCELADA_TUTOR']]);

test('terceros y administradores no pueden cancelar', function (bool $admin) {
    $tutoria = Tutoria::factory()->create();
    Sanctum::actingAs(User::factory()->create(['rol' => $admin ? 'ADMIN' : 'ESTUDIANTE']));
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => 'Privado'])->assertForbidden();
    expect($tutoria->fresh()->estado)->toBe('CONFIRMADA');
})->with([false, true]);

test('exige un motivo válido', function (mixed $motivo) {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-24']);
    Sanctum::actingAs($tutoria->estudiante);
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => $motivo])->assertUnprocessable()->assertJsonValidationErrors('motivo');
})->with([[null], ['   '], [str_repeat('a', 501)], [['invalido']]]);

test('rechaza cancelaciones sin anticipación', function () {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-23', 'hora_inicio' => 8]);
    Sanctum::actingAs($tutoria->estudiante);
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => 'Privado'])->assertUnprocessable()->assertJsonValidationErrors('tutoria');
    expect($tutoria->fresh()->estado)->toBe('CONFIRMADA');
    $this->assertDatabaseCount('tutorias_no_realizadas', 0);
});
