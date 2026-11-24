<?php

use App\Models\PerfilTutor;
use App\Models\Tutoria;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function tutoriaCompletadaConPerfil(): Tutoria
{
    $tutoria = Tutoria::factory()->estado('COMPLETADA')->create();
    PerfilTutor::factory()->aprobado()->create(['user_id' => $tutoria->tutor_id]);

    return $tutoria;
}

test('el estudiante califica una tutoría completada', function () {
    $tutoria = tutoriaCompletadaConPerfil();
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", [
        'puntuacion' => 5,
        'comentario' => '  Explicó muy claro los ejercicios.  ',
    ])->assertCreated()
        ->assertJsonPath('data.tutoria_id', $tutoria->id)
        ->assertJsonPath('data.puntuacion', 5)
        ->assertJsonPath('data.comentario', 'Explicó muy claro los ejercicios.');

    $this->assertDatabaseHas('evaluaciones', [
        'tutoria_id' => $tutoria->id,
        'estudiante_id' => $tutoria->estudiante_id,
        'tutor_id' => $tutoria->tutor_id,
        'puntuacion' => 5,
    ]);
    $this->assertDatabaseHas('bitacora', [
        'tipo_evento' => 'TUTORIA_CALIFICADA',
        'entidad' => 'evaluaciones',
        'requiere_atencion' => false,
    ]);
});

test('recalcula el total y el promedio del tutor', function () {
    $primera = tutoriaCompletadaConPerfil();
    $segunda = Tutoria::factory()->estado('COMPLETADA')->create([
        'estudiante_id' => $primera->estudiante_id,
        'tutor_id' => $primera->tutor_id,
    ]);
    Sanctum::actingAs($primera->estudiante);

    $this->postJson("/api/v1/tutorias/{$primera->id}/calificar", ['puntuacion' => 5])->assertCreated();
    $perfil = PerfilTutor::where('user_id', $primera->tutor_id)->firstOrFail();
    expect($perfil->total_calificaciones)->toBe(1)
        ->and($perfil->promedio_calificacion)->toBe('5.00');

    $this->postJson("/api/v1/tutorias/{$segunda->id}/calificar", ['puntuacion' => 4])->assertCreated();
    $perfil->refresh();
    expect($perfil->total_calificaciones)->toBe(2)
        ->and($perfil->promedio_calificacion)->toBe('4.50');
});

test('una tutoría solo se puede calificar una vez', function () {
    $tutoria = tutoriaCompletadaConPerfil();
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", ['puntuacion' => 5])->assertCreated();
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", ['puntuacion' => 4])
        ->assertConflict()->assertJsonPath('message', 'Ya calificaste esta tutoría.');
});

test('solo se califican tutorías completadas', function (string $estado) {
    $tutoria = Tutoria::factory()->estado($estado)->create();
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", ['puntuacion' => 5])
        ->assertConflict()->assertJsonPath('message', 'Solo puedes calificar una tutoría que se realizó.');
})->with(['CONFIRMADA', 'CANCELADA_ESTUDIANTE']);

test('solo el estudiante de la tutoría puede calificar', function (string $actor) {
    $tutoria = tutoriaCompletadaConPerfil();
    $usuario = match ($actor) {
        'tutor' => $tutoria->tutor,
        'administrador' => User::factory()->create(['rol' => 'ADMIN']),
        default => User::factory()->create(),
    };
    Sanctum::actingAs($usuario);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", ['puntuacion' => 5])->assertForbidden();
    $this->assertDatabaseCount('evaluaciones', 0);
})->with(['tutor', 'ajeno', 'administrador']);

test('la puntuación debe ser un entero entre uno y cinco', function (mixed $puntuacion) {
    $tutoria = tutoriaCompletadaConPerfil();
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", ['puntuacion' => $puntuacion])
        ->assertUnprocessable()->assertJsonValidationErrors('puntuacion');
})->with([0, 6, 'cinco', null]);

test('el comentario no puede superar mil caracteres', function () {
    $tutoria = tutoriaCompletadaConPerfil();
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", [
        'puntuacion' => 5,
        'comentario' => str_repeat('a', 1001),
    ])->assertUnprocessable()->assertJsonValidationErrors('comentario');
});

test('la petición exige autenticación', function () {
    $tutoria = tutoriaCompletadaConPerfil();

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", ['puntuacion' => 5])->assertUnauthorized();
});

test('el comentario es opcional y los identificadores están prohibidos', function () {
    $tutoria = tutoriaCompletadaConPerfil();
    Sanctum::actingAs($tutoria->estudiante);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/calificar", ['puntuacion' => 5])
        ->assertCreated()->assertJsonPath('data.comentario', null);
    $this->assertDatabaseHas('evaluaciones', ['tutoria_id' => $tutoria->id, 'comentario' => null]);

    $otra = Tutoria::factory()->estado('COMPLETADA')->create(['estudiante_id' => $tutoria->estudiante_id]);
    $this->postJson("/api/v1/tutorias/{$otra->id}/calificar", [
        'puntuacion' => 5,
        'tutor_id' => $tutoria->estudiante_id,
    ])->assertUnprocessable()->assertJsonValidationErrors('tutor_id');
});
