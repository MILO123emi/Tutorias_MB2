<?php

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('las carreras exigen token', function () {
    $this->getJson('/api/v1/carreras')->assertStatus(401);
});

test('lista las carreras con token', function () {
    Carrera::factory()->count(3)->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/carreras')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('lista las materias filtradas por carrera', function () {
    $carrera = Carrera::factory()->create();
    Materia::factory()->count(2)->create(['carrera_id' => $carrera->id]);
    Materia::factory()->count(3)->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/materias?carrera_id='.$carrera->id)
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
