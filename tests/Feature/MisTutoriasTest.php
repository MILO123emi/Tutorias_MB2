<?php

use App\Models\Tutoria;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('lista las próximas tutorías del estudiante en orden de fecha', function () {
    $estudiante = User::factory()->create();
    $despues = Tutoria::factory()->create(['estudiante_id' => $estudiante->id, 'fecha' => now()->addDays(5)->toDateString()]);
    $antes = Tutoria::factory()->create(['estudiante_id' => $estudiante->id, 'fecha' => now()->addDays(1)->toDateString()]);
    Tutoria::factory()->estado('COMPLETADA')->create(['estudiante_id' => $estudiante->id]);
    Tutoria::factory()->create(); // de otro estudiante
    Sanctum::actingAs($estudiante);

    $this->getJson('/api/v1/mis-tutorias?tipo=proximas')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $antes->id)
        ->assertJsonPath('data.1.id', $despues->id)
        ->assertJsonStructure(['data' => [['id', 'tema', 'fecha', 'hora_inicio', 'hora_fin', 'modalidad', 'estado', 'materia' => ['id', 'nombre'], 'tutor' => ['id', 'name', 'foto_url'], 'calificada']]]);
});

test('el historial trae las que ya no están confirmadas', function () {
    $estudiante = User::factory()->create();
    Tutoria::factory()->create(['estudiante_id' => $estudiante->id]);
    Tutoria::factory()->estado('COMPLETADA')->create(['estudiante_id' => $estudiante->id]);
    Tutoria::factory()->estado('CANCELADA_TUTOR')->create(['estudiante_id' => $estudiante->id]);
    Sanctum::actingAs($estudiante);

    $this->getJson('/api/v1/mis-tutorias?tipo=historial')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/mis-tutorias?tipo=otra')->assertStatus(422);
});

test('pide token para ver mis tutorías', function () {
    $this->getJson('/api/v1/mis-tutorias')->assertUnauthorized();
});
