<?php

use App\Models\Tutoria;
use App\Models\TutoriaNoRealizada;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function crearNoRealizada(string $tipo, array $tutoria = [], bool $revisada = false): TutoriaNoRealizada
{
    $t = Tutoria::factory()->estado($tipo)->create($tutoria);
    $noRealizada = TutoriaNoRealizada::create([
        'tutoria_id' => $t->id,
        'tipo' => $tipo,
        'reportado_por' => $tipo === 'SIN_CONFIRMAR' ? null : $t->estudiante_id,
        'motivo_para_admin' => 'Motivo que solo ve administración.',
    ]);
    if ($revisada) {
        $noRealizada->forceFill(['revisado_at' => now()])->save();
    }

    return $noRealizada;
}

test('admin lista las no realizadas con la tutoria y el motivo', function () {
    $noRealizada = crearNoRealizada('NO_ASISTIO_TUTOR');
    crearNoRealizada('SIN_CONFIRMAR');
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/admin/no-realizadas')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonFragment([
            'id' => $noRealizada->id,
            'tipo' => 'NO_ASISTIO_TUTOR',
            'motivo_para_admin' => 'Motivo que solo ve administración.',
            'revisado' => false,
        ])
        ->assertJsonStructure(['data' => [['tutoria' => ['id', 'fecha', 'tutor' => ['telefono'], 'estudiante' => ['telefono']]]]]);
});

test('filtra por tipo, revisado, fechas y participantes', function () {
    $tutor = User::factory()->create();
    $deEsteTutor = crearNoRealizada('CANCELADA_TUTOR', ['tutor_id' => $tutor->id, 'fecha' => '2026-11-10']);
    crearNoRealizada('CANCELADA_ESTUDIANTE', ['fecha' => '2026-11-20'], revisada: true);
    crearNoRealizada('SIN_CONFIRMAR', ['fecha' => '2026-11-25']);
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/admin/no-realizadas?tipo=CANCELADA_TUTOR')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $deEsteTutor->id);
    $this->getJson('/api/v1/admin/no-realizadas?revisado=1')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.tipo', 'CANCELADA_ESTUDIANTE');
    $this->getJson('/api/v1/admin/no-realizadas?revisado=false')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/admin/no-realizadas?desde=2026-11-15&hasta=2026-11-24')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.tipo', 'CANCELADA_ESTUDIANTE');
    $this->getJson("/api/v1/admin/no-realizadas?tutor_id={$tutor->id}")
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $deEsteTutor->id);
});

test('rechaza filtros invalidos', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/admin/no-realizadas?tipo=OTRO')->assertUnprocessable()->assertJsonValidationErrors('tipo');
    $this->getJson('/api/v1/admin/no-realizadas?desde=2026-11-20&hasta=2026-11-01')
        ->assertUnprocessable()->assertJsonValidationErrors('hasta');
});
