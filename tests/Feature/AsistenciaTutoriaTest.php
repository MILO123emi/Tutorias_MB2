<?php

use App\Models\Parametro;
use App\Models\Tutoria;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(12, 0));
    Parametro::query()->updateOrCreate(['clave' => 'horas_para_confirmar'], ['valor' => '24']);
    Parametro::query()->updateOrCreate(['clave' => 'horas_para_sin_confirmar'], ['valor' => '48']);
    Cache::flush();
});

test('el tutor confirma la asistencia y la tutoría queda completada', function () {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-23', 'hora_inicio' => 10]);
    Sanctum::actingAs($tutoria->tutor);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/asistencia")
        ->assertOk()->assertJsonPath('data.estado', 'COMPLETADA')->assertJsonPath('data.asistencia_confirmada', true);

    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'ASISTENCIA_CONFIRMADA', 'entidad_id' => $tutoria->id]);
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/asistencia")->assertConflict();
});

test('solo el tutor de la tutoría confirma asistencia', function (string $quien) {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-23', 'hora_inicio' => 10]);
    $actor = $quien === 'estudiante' ? $tutoria->estudiante : User::factory()->create(['rol' => $quien]);
    Sanctum::actingAs($actor);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/asistencia")->assertForbidden();
    expect($tutoria->fresh()->estado)->toBe('CONFIRMADA');
})->with(['estudiante', 'ADMIN', 'ESTUDIANTE']);

test('la asistencia respeta la ventana de confirmación', function (string $fecha, int $hora) {
    $tutoria = Tutoria::factory()->create(['fecha' => $fecha, 'hora_inicio' => $hora]);
    Sanctum::actingAs($tutoria->tutor);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/asistencia")
        ->assertUnprocessable()->assertJsonValidationErrors('tutoria');
    expect($tutoria->fresh()->estado)->toBe('CONFIRMADA');
})->with([
    'todavía no empieza' => ['2026-11-23', 13],
    'pasó el plazo de 24 horas' => ['2026-11-22', 10],
]);

test('cada participante reporta la inasistencia de la otra parte', function (string $participante, string $estado) {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-23', 'hora_inicio' => 10]);
    $actor = $tutoria->$participante;
    $otraParte = $participante === 'estudiante' ? $tutoria->tutor : $tutoria->estudiante;
    Sanctum::actingAs($actor);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/inasistencia", ['motivo' => '  Motivo privado  '])
        ->assertOk()->assertJsonPath('data.estado', $estado)->assertDontSee('Motivo privado');

    $this->assertDatabaseHas('tutorias_no_realizadas', [
        'tutoria_id' => $tutoria->id, 'tipo' => $estado, 'reportado_por' => $actor->id, 'motivo_para_admin' => 'Motivo privado',
    ]);
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'INASISTENCIA_REPORTADA', 'entidad_id' => $tutoria->id, 'requiere_atencion' => true]);
    expect(json_encode($otraParte->notifications()->sole()->data))->not->toContain('Motivo privado');
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/inasistencia", ['motivo' => 'Otra vez'])->assertConflict();
})->with([['estudiante', 'NO_ASISTIO_TUTOR'], ['tutor', 'NO_ASISTIO_ESTUDIANTE']]);

test('terceros y administradores no reportan inasistencias', function (string $rol) {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-23', 'hora_inicio' => 10]);
    Sanctum::actingAs(User::factory()->create(['rol' => $rol]));

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/inasistencia", ['motivo' => 'Privado'])->assertForbidden();
    $this->assertDatabaseCount('tutorias_no_realizadas', 0);
})->with(['ESTUDIANTE', 'ADMIN']);

test('la inasistencia exige motivo y respeta su ventana', function (string $fecha, int $hora, mixed $motivo, string $campo) {
    $tutoria = Tutoria::factory()->create(['fecha' => $fecha, 'hora_inicio' => $hora]);
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/inasistencia", ['motivo' => $motivo])
        ->assertUnprocessable()->assertJsonValidationErrors($campo);
    expect($tutoria->fresh()->estado)->toBe('CONFIRMADA');
})->with([
    'sin motivo' => ['2026-11-23', 10, '   ', 'motivo'],
    'motivo largo' => ['2026-11-23', 10, str_repeat('a', 501), 'motivo'],
    'todavía no empieza' => ['2026-11-23', 13, 'Motivo', 'tutoria'],
    'pasó el plazo de 48 horas' => ['2026-11-21', 10, 'Motivo', 'tutoria'],
]);

test('el comando cierra solo las tutorías vencidas sin confirmar', function () {
    $vencida = Tutoria::factory()->create(['fecha' => '2026-11-21', 'hora_inicio' => 10]);
    $enPlazo = Tutoria::factory()->create(['fecha' => '2026-11-21', 'hora_inicio' => 12]);
    $completada = Tutoria::factory()->estado('COMPLETADA')->create(['fecha' => '2026-11-20', 'hora_inicio' => 10]);

    $this->artisan('tutorias:cerrar-vencidas')->assertSuccessful();
    $this->artisan('tutorias:cerrar-vencidas')->assertSuccessful();

    expect($vencida->fresh()->estado)->toBe('SIN_CONFIRMAR')
        ->and($enPlazo->fresh()->estado)->toBe('CONFIRMADA')
        ->and($completada->fresh()->estado)->toBe('COMPLETADA');
    $this->assertDatabaseCount('tutorias_no_realizadas', 1);
    $this->assertDatabaseHas('tutorias_no_realizadas', ['tutoria_id' => $vencida->id, 'tipo' => 'SIN_CONFIRMAR', 'reportado_por' => null]);
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'TUTORIA_SIN_CONFIRMAR', 'actor_id' => null, 'entidad_id' => $vencida->id]);
});

test('los errores del API responden en español y sin detalles internos', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/tutorias/999999')->assertNotFound()->assertExactJson(['message' => 'No encontramos lo que buscabas.']);
    $tutoria = Tutoria::factory()->create();
    $this->getJson("/api/v1/tutorias/{$tutoria->id}")->assertForbidden()->assertExactJson(['message' => 'No tienes permiso para realizar esta acción.']);
});

test('el API exige sesión con un mensaje en español', function () {
    $this->getJson('/api/v1/mis-tutorias')->assertUnauthorized()->assertExactJson(['message' => 'Tu sesión venció. Inicia sesión de nuevo.']);
});

test('los errores de validación llegan en español', function () {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-24']);
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", [])
        ->assertUnprocessable()->assertJsonPath('errors.motivo.0', 'El campo motivo es obligatorio.');
});

test('el API responde 429 en español cuando se pasa el límite de intentos', function () {
    $respuesta = null;

    foreach (range(1, 11) as $intento) {
        $respuesta = $this->postJson('/api/v1/auth/login', [
            'email' => "invalido{$intento}@unicah.edu",
            'password' => 'incorrecta',
        ]);
    }

    $respuesta->assertStatus(429)
        ->assertExactJson(['message' => 'Demasiados intentos. Espera un momento.']);
});
