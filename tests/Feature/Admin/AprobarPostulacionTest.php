<?php

use App\Models\Bitacora;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\PerfilTutor;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function adminYPostulante(): array
{
    $admin = User::factory()->admin()->create();
    $carrera = Carrera::factory()->create();
    $postulante = User::factory()->create(['carrera_id' => $carrera->id]);
    $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
    $perfil = PerfilTutor::factory()->create(['user_id' => $postulante->id]);
    $postulante->materias()->sync([$materia->id]);
    Bitacora::create(['actor_id' => $postulante->id, 'tipo_evento' => 'TUTOR_POSTULADO', 'entidad' => 'perfil_tutor', 'entidad_id' => $perfil->id, 'detalle' => 'Postuló', 'requiere_atencion' => true]);

    return [$admin, $postulante, $materia, $perfil];
}

test('admin aprueba la postulacion y el estudiante deriva a tutor aprobado', function () {
    [$admin, $postulante, $materia, $perfil] = adminYPostulante();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/aprobar")
        ->assertOk()->assertJsonPath('data.estado', 'APROBADO')->assertJsonPath('data.postulante.id', $postulante->id);

    expect($postulante->fresh()->esTutorAprobado())->toBeTrue();
    expect($postulante->fresh()->rol)->toBe('ESTUDIANTE');
    $this->assertDatabaseHas('perfil_tutor', ['id' => $perfil->id, 'aprobado_por' => $admin->id]);
    $this->assertDatabaseHas('tutor_materias', ['tutor_id' => $postulante->id, 'materia_id' => $materia->id]);
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'TUTOR_APROBADO', 'entidad_id' => $perfil->id]);
    expect(Bitacora::where('tipo_evento', 'TUTOR_POSTULADO')->where('entidad_id', $perfil->id)->first()->revisado_at)->not->toBeNull();
    $this->assertDatabaseHas('notifications', ['notifiable_id' => $postulante->id]);
});

test('admin puede reemplazar materias y no puede asignar otra carrera', function () {
    [$admin, $postulante, $materia, $perfil] = adminYPostulante();
    $otraCarrera = Carrera::factory()->create();
    $materiaAjena = Materia::factory()->create(['carrera_id' => $otraCarrera->id]);
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/aprobar", ['materias' => [$materiaAjena->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('materias.0');
    $otra = Materia::factory()->create(['carrera_id' => $postulante->carrera_id]);
    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/aprobar", ['materias' => [$otra->id]])->assertOk();
    $this->assertDatabaseMissing('tutor_materias', ['tutor_id' => $postulante->id, 'materia_id' => $materia->id]);
});

test('rechaza postulacion con motivo y permite ver solo las pendientes en orden', function () {
    [$admin, $postulante, , $perfil] = adminYPostulante();
    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/rechazar", ['motivo' => '   Información insuficiente para aprobar.  '])
        ->assertOk()->assertJsonPath('data.estado', 'RECHAZADO')->assertJsonPath('data.motivo_rechazo', 'Información insuficiente para aprobar.');
    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/rechazar", ['motivo' => 'corto'])->assertUnprocessable();
    $this->getJson('/api/v1/admin/postulaciones')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/postulaciones/mia')->assertOk()->assertJsonMissingPath('data.postulante');
});

test('lista postulaciones pendientes primero en orden de llegada con postulante y carrera', function () {
    $admin = User::factory()->admin()->create();
    $carrera = Carrera::factory()->create();
    $antiguo = User::factory()->create(['carrera_id' => $carrera->id]);
    $nuevo = User::factory()->create(['carrera_id' => $carrera->id]);
    $primero = PerfilTutor::factory()->create(['user_id' => $antiguo->id, 'created_at' => now()->subDay()]);
    PerfilTutor::factory()->create(['user_id' => $nuevo->id, 'created_at' => now()]);
    PerfilTutor::factory()->create(['estado' => 'APROBADO']);
    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/postulaciones')->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $primero->id)
        ->assertJsonPath('data.0.postulante.id', $antiguo->id)
        ->assertJsonPath('data.0.postulante.carrera.id', $carrera->id);
});

test('rechaza la aprobacion de postulante sin materias y de cuenta suspendida', function () {
    [$admin, $postulante, , $perfil] = adminYPostulante();
    $postulante->materias()->detach();
    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/aprobar")
        ->assertUnprocessable()->assertJsonValidationErrors('materias');

    $postulante->forceFill(['activo' => false])->save();
    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/rechazar", ['motivo' => 'No cumple requisitos.'])
        ->assertConflict()->assertJsonPath('message', 'La cuenta del postulante está suspendida.');
});

test('el tutor aprobado entra a su agenda y aparece en la busqueda de tutores', function () {
    [$admin, $postulante, , $perfil] = adminYPostulante();

    Sanctum::actingAs($postulante);
    $this->getJson('/api/v1/mi-agenda')->assertForbidden();

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/aprobar")->assertOk();

    Sanctum::actingAs($postulante);
    $this->getJson('/api/v1/mi-agenda')->assertOk();

    $otro = User::factory()->create(['carrera_id' => $postulante->carrera_id]);
    Sanctum::actingAs($otro);
    $this->getJson('/api/v1/tutores')->assertOk()->assertJsonFragment(['id' => $postulante->id]);
});

test('no se puede revisar una postulacion que ya no esta pendiente', function (string $accion, string $estado) {
    [$admin, , , $perfil] = adminYPostulante();
    $perfil->forceFill(['estado' => $estado])->save();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/{$accion}", ['motivo' => 'Motivo suficientemente largo.'])
        ->assertConflict()->assertJsonPath('message', 'Esta postulación ya fue revisada.');

    expect($perfil->fresh()->estado)->toBe($estado);
})->with([
    ['aprobar', 'APROBADO'], ['aprobar', 'RECHAZADO'], ['aprobar', 'INACTIVO'],
    ['rechazar', 'APROBADO'], ['rechazar', 'RECHAZADO'], ['rechazar', 'INACTIVO'],
]);

test('despues de un rechazo el estudiante puede volver a postularse', function () {
    [$admin, $postulante, $materia, $perfil] = adminYPostulante();

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/rechazar", ['motivo' => 'Información insuficiente para aprobar.'])->assertOk();

    Sanctum::actingAs($postulante);
    $this->postJson('/api/v1/postulaciones', ['materias' => [$materia->id]])
        ->assertCreated()
        ->assertJsonPath('data.estado', 'PENDIENTE')
        ->assertJsonPath('data.motivo_rechazo', null);

    Sanctum::actingAs($admin);
    $this->getJson('/api/v1/admin/postulaciones')->assertOk()->assertJsonPath('data.0.id', $perfil->id);
});

test('aprobar no acepta campos que decide el servidor', function () {
    [$admin, , , $perfil] = adminYPostulante();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/postulaciones/{$perfil->id}/aprobar", ['estado' => 'RECHAZADO'])
        ->assertUnprocessable()->assertJsonValidationErrors('estado');

    expect($perfil->fresh()->estado)->toBe('PENDIENTE');
});
