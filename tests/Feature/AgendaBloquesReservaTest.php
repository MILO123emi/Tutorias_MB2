<?php

use App\Models\PerfilTutor;
use App\Models\Tutoria;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(8, 0));
    Mail::fake();
});

test('cancelar libera el bloque y permite reservarlo nuevamente', function () {
    $tutor = User::factory()->create();
    PerfilTutor::factory()->aprobado()->create(['user_id' => $tutor->id]);
    $tutoria = Tutoria::factory()->create(['tutor_id' => $tutor->id, 'fecha' => '2026-11-24', 'hora_inicio' => 14]);
    $tutor->materias()->attach($tutoria->materia_id);
    $tutor->disponibilidad()->create(['dia_semana' => 2, 'hora_inicio' => 14, 'modalidad' => 'VIRTUAL', 'lugar_o_enlace' => 'https://example.com/reunion']);
    Sanctum::actingAs($tutoria->estudiante);
    $url = "/api/v1/tutores/{$tutor->id}/bloques?fecha=2026-11-24";
    $this->getJson($url)->assertOk()->assertJsonPath('data.0.disponible', false);
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => 'Cambio de planes'])->assertOk();
    $this->getJson($url)->assertOk()->assertJsonPath('data.0.disponible', true)->assertJsonPath('data.0.hora_fin', 15);
    $this->postJson('/api/v1/tutorias', [
        'tutor_id' => $tutor->id, 'materia_id' => $tutoria->materia_id,
        'fecha' => '2026-11-24', 'hora_inicio' => 14, 'tema' => 'Repaso',
    ])->assertCreated()->assertJsonPath('data.estado', 'CONFIRMADA');
    $this->getJson($url)->assertOk()->assertJsonPath('data.0.disponible', false);
});

test('disponibilidad y agenda requieren tutor aprobado', function (string $estado) {
    $usuario = User::factory()->create();
    if ($estado !== 'SIN_PERFIL') {
        PerfilTutor::factory()->create(['user_id' => $usuario->id, 'estado' => $estado]);
    }
    Sanctum::actingAs($usuario);
    $this->getJson('/api/v1/mi-agenda')->assertForbidden();
    $this->getJson('/api/v1/mi-disponibilidad')->assertForbidden();
    $this->putJson('/api/v1/mi-disponibilidad', ['bloques' => []])->assertForbidden();
})->with(['SIN_PERFIL', 'PENDIENTE']);

test('agenda de hoy excluye canceladas y conserva próximas e historial', function () {
    $tutor = User::factory()->create();
    PerfilTutor::factory()->aprobado()->create(['user_id' => $tutor->id]);
    foreach (['CONFIRMADA', 'CANCELADA_TUTOR', 'CANCELADA_ESTUDIANTE'] as $i => $estado) {
        Tutoria::factory()->create(['tutor_id' => $tutor->id, 'fecha' => '2026-11-23', 'hora_inicio' => 14 + $i, 'estado' => $estado]);
    }
    Sanctum::actingAs($tutor);
    $this->getJson('/api/v1/mi-agenda?tipo=hoy')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.estado', 'CONFIRMADA');
    $this->getJson('/api/v1/mi-agenda?tipo=proximas')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/mi-agenda?tipo=historial')->assertOk()->assertJsonCount(2, 'data');
});
