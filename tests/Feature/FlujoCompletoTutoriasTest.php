<?php

// Recorre las tareas 5.5, 5.7 y 5.9 como las usa la app: varios roles sobre la misma tutoría,
// casos límite de la reserva y formato uniforme de los errores.

use App\Models\Materia;
use App\Models\Parametro;
use App\Models\PerfilTutor;
use App\Models\Tutoria;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Lunes 23/11: el martes 24 cumple la anticipación y el domingo 29 sirve para el caso sin tutorías.
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(8, 0, 0));
    Mail::fake();
    // Igual que en el servidor real: sin modo debug los errores no traen trazas.
    config(['app.debug' => false]);
    foreach (['max_tutorias_por_dia' => '4', 'anticipacion_min_minutos' => '60', 'horas_para_confirmar' => '24', 'horas_para_sin_confirmar' => '48'] as $clave => $valor) {
        Parametro::query()->updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
    Cache::flush();
    $this->materia = Materia::factory()->create();
});

// Tutor aprobado que da la materia y tiene publicados los bloques del martes.
function tutorConBloques(Materia $materia, array $horas = [14]): User
{
    $tutor = User::factory()->create();
    PerfilTutor::factory()->aprobado()->create(['user_id' => $tutor->id]);
    $tutor->materias()->attach($materia->id);
    foreach ($horas as $hora) {
        $tutor->disponibilidad()->create(['dia_semana' => 2, 'hora_inicio' => $hora, 'modalidad' => 'VIRTUAL', 'lugar_o_enlace' => 'https://example.com/sala']);
    }

    return $tutor;
}

function datosReserva(User $tutor, Materia $materia, array $cambios = []): array
{
    return [...[
        'tutor_id' => $tutor->id, 'materia_id' => $materia->id, 'fecha' => '2026-11-24',
        'hora_inicio' => 14, 'tema' => 'Repaso de integrales', 'descripcion' => 'Necesito practicar para el parcial.',
    ], ...$cambios];
}

test('flujo completo: publicar, reservar, ver agenda, confirmar y pasar al historial', function () {
    $tutor = User::factory()->create();
    PerfilTutor::factory()->aprobado()->create(['user_id' => $tutor->id]);
    $tutor->materias()->attach($this->materia->id);
    $estudiante = User::factory()->create();

    Sanctum::actingAs($tutor);
    $this->putJson('/api/v1/mi-disponibilidad', ['bloques' => [
        ['dia_semana' => 2, 'hora_inicio' => 14, 'modalidad' => 'PRESENCIAL', 'lugar_o_enlace' => 'Aula 3'],
    ]])->assertOk();

    Sanctum::actingAs($estudiante);
    $this->getJson("/api/v1/tutores/{$tutor->id}/bloques?fecha=2026-11-24")
        ->assertOk()->assertJsonPath('data.0.disponible', true)->assertJsonPath('data.0.lugar_o_enlace', 'Aula 3');
    $id = $this->postJson('/api/v1/tutorias', datosReserva($tutor, $this->materia))
        ->assertCreated()->assertJsonPath('data.modalidad', 'PRESENCIAL')->json('data.id');
    expect($tutor->notifications()->count())->toBe(1);

    Sanctum::actingAs($tutor);
    $this->getJson('/api/v1/mi-agenda?tipo=proximas')->assertOk()->assertJsonPath('data.0.id', $id)
        ->assertJsonPath('data.0.estudiante.id', $estudiante->id);
    $this->postJson("/api/v1/tutorias/{$id}/asistencia")->assertUnprocessable();

    $this->travelTo(now()->setDate(2026, 11, 24)->setTime(14, 30));
    $this->postJson("/api/v1/tutorias/{$id}/asistencia")->assertOk()
        ->assertJsonPath('data.estado', 'COMPLETADA')->assertJsonPath('data.asistencia_confirmada', true);
    $this->postJson("/api/v1/tutorias/{$id}/asistencia")->assertConflict();

    Sanctum::actingAs($estudiante);
    $this->getJson('/api/v1/mis-tutorias?tipo=proximas')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/mis-tutorias?tipo=historial')->assertOk()->assertJsonPath('data.0.estado', 'COMPLETADA');
});

test('dos estudiantes no pueden quedarse con el mismo bloque', function () {
    $tutor = tutorConBloques($this->materia);
    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/v1/tutorias', datosReserva($tutor, $this->materia))->assertCreated();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/v1/tutorias', datosReserva($tutor, $this->materia))
        ->assertConflict()->assertExactJson(['message' => 'Ese bloque ya fue reservado.']);
    $this->getJson("/api/v1/tutores/{$tutor->id}/bloques?fecha=2026-11-24")
        ->assertJsonPath('data.0.estado', 'ocupado')->assertJsonPath('data.0.motivo', 'bloque_reservado');
    expect(Tutoria::count())->toBe(1);
});

test('un estudiante no puede tener dos tutorías a la misma hora', function () {
    $primero = tutorConBloques($this->materia);
    $segundo = tutorConBloques($this->materia);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/tutorias', datosReserva($primero, $this->materia))->assertCreated();
    $this->postJson('/api/v1/tutorias', datosReserva($segundo, $this->materia))
        ->assertUnprocessable()->assertJsonValidationErrors('hora_inicio');
});

test('la reserva rechaza fechas y bloques inválidos', function (array $cambios, string $campo) {
    $tutor = tutorConBloques($this->materia);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/tutorias', datosReserva($tutor, $this->materia, $cambios))
        ->assertUnprocessable()->assertJsonValidationErrors($campo);
    expect(Tutoria::count())->toBe(0);
})->with([
    'domingo' => [['fecha' => '2026-11-29'], 'fecha'],
    'fecha pasada' => [['fecha' => '2026-11-22'], 'fecha'],
    'más de 7 días' => [['fecha' => '2026-12-01'], 'fecha'],
    'fecha imposible' => [['fecha' => '2026-02-31'], 'fecha'],
    'bloque no publicado' => [['hora_inicio' => 15], 'hora_inicio'],
    'hora fuera de rango' => [['hora_inicio' => 21], 'hora_inicio'],
    'sin tema' => [['tema' => ''], 'tema'],
    'el cliente no elige el estado' => [['estado' => 'COMPLETADA'], 'estado'],
    'el cliente no elige la modalidad' => [['modalidad' => 'PRESENCIAL'], 'modalidad'],
]);

test('no se reserva una materia que el tutor no imparte ni sin la anticipación mínima', function () {
    $tutor = tutorConBloques($this->materia);
    Sanctum::actingAs(User::factory()->create());
    $otra = Materia::factory()->create();

    $this->postJson('/api/v1/tutorias', datosReserva($tutor, $otra))->assertUnprocessable()->assertJsonValidationErrors('materia_id');

    $this->travelTo(now()->setDate(2026, 11, 24)->setTime(13, 30));
    $this->postJson('/api/v1/tutorias', datosReserva($tutor, $this->materia))->assertUnprocessable()->assertJsonValidationErrors('hora_inicio');
});

test('el cupo diario del tutor se respeta en la reserva y en los bloques', function () {
    Parametro::query()->where('clave', 'max_tutorias_por_dia')->update(['valor' => '1']);
    Cache::flush();
    $tutor = tutorConBloques($this->materia, [14, 15]);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/tutorias', datosReserva($tutor, $this->materia))->assertCreated();
    $this->getJson("/api/v1/tutores/{$tutor->id}/bloques?fecha=2026-11-24")
        ->assertJsonPath('data.1.disponible', false)->assertJsonPath('data.1.motivo', 'cupo_del_dia_lleno');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/v1/tutorias', datosReserva($tutor, $this->materia, ['hora_inicio' => 15]))
        ->assertUnprocessable()->assertJsonValidationErrors('tutor_id');
});

test('cancelar dos veces responde conflicto y el motivo no se le muestra a la otra parte', function () {
    $tutor = tutorConBloques($this->materia);
    $estudiante = User::factory()->create();
    $tutoria = Tutoria::factory()->create(['tutor_id' => $tutor->id, 'estudiante_id' => $estudiante->id, 'materia_id' => $this->materia->id, 'fecha' => '2026-11-24', 'hora_inicio' => 14]);

    Sanctum::actingAs($tutor);
    $respuesta = $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => 'Motivo privado del tutor'])
        ->assertOk()->assertJsonPath('data.estado', 'CANCELADA_TUTOR');
    expect($respuesta->getContent())->not->toContain('Motivo privado');
    expect($estudiante->notifications()->count())->toBe(1);
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => 'Otra vez'])->assertConflict();

    Sanctum::actingAs($estudiante);
    expect($this->getJson("/api/v1/tutorias/{$tutoria->id}")->assertOk()->getContent())->not->toContain('Motivo privado');
});

test('una tutoría cerrada no se puede cancelar, confirmar ni reportar', function (string $estado) {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-23', 'hora_inicio' => 7, 'estado' => $estado]);
    Sanctum::actingAs($tutoria->tutor);

    $this->postJson("/api/v1/tutorias/{$tutoria->id}/cancelar", ['motivo' => 'Prueba'])->assertConflict();
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/asistencia")->assertConflict();
    $this->postJson("/api/v1/tutorias/{$tutoria->id}/inasistencia", ['motivo' => 'Prueba'])->assertConflict();
    expect($tutoria->fresh()->estado)->toBe($estado);
})->with(['COMPLETADA', 'SIN_CONFIRMAR', 'NO_ASISTIO_TUTOR', 'NO_ASISTIO_ESTUDIANTE', 'CANCELADA_TUTOR', 'CANCELADA_ESTUDIANTE']);

test('el cierre automático respeta el límite exacto y no repite el trabajo', function () {
    $tutoria = Tutoria::factory()->create(['fecha' => '2026-11-23', 'hora_inicio' => 9]);

    // Termina a las 10:00 del 23; el plazo de 48 horas vence a las 10:00 del 25.
    $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 0, 0));
    $this->artisan('tutorias:cerrar-vencidas')->assertSuccessful();
    expect($tutoria->fresh()->estado)->toBe('CONFIRMADA');

    $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 0, 1));
    $this->artisan('tutorias:cerrar-vencidas')->assertSuccessful();
    $this->artisan('tutorias:cerrar-vencidas')->assertSuccessful();
    expect($tutoria->fresh()->estado)->toBe('SIN_CONFIRMAR');
    $this->assertDatabaseCount('tutorias_no_realizadas', 1);
});

test('el cierre de tutorías vencidas está programado cada hora', function () {
    $evento = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains((string) $e->command, 'tutorias:cerrar-vencidas'));

    expect($evento)->not->toBeNull()->and($evento->expression)->toBe('0 * * * *');
});

test('los errores del API mantienen el formato que espera la app', function () {
    $tutoria = Tutoria::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/tutorias/abc')->assertNotFound()->assertExactJson(['message' => 'No encontramos lo que buscabas.']);
    $this->getJson("/api/v1/tutorias/{$tutoria->id}")->assertForbidden()->assertJsonStructure(['message']);
    $this->postJson('/api/v1/tutorias', [])->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['tutor_id', 'fecha', 'hora_inicio', 'materia_id', 'tema']])
        ->assertJsonPath('errors.tema.0', 'El campo tema es obligatorio.');
});

test('una cuenta suspendida no puede seguir usando su sesión', function () {
    $usuario = User::factory()->create(['activo' => false]);
    Sanctum::actingAs($usuario);

    $this->getJson('/api/v1/mis-tutorias')->assertForbidden()->assertJsonStructure(['message']);
});

test('la factory no repite fecha y hora para un mismo estudiante', function () {
    $estudiante = User::factory()->create();

    Tutoria::factory()->count(98)->create(['estudiante_id' => $estudiante->id]);

    expect($estudiante->tutoriasComoEstudiante()->count())->toBe(98);
});

test('la colección de Postman documenta bloques, reservas, cancelación y asistencia', function () {
    $coleccion = json_decode(file_get_contents(base_path('docs/postman/Tutorias_API.postman_collection.json')), true, flags: JSON_THROW_ON_ERROR);
    $urls = collect($coleccion['item'])->flatMap(fn ($grupo) => $grupo['item'] ?? [$grupo])
        ->map(fn ($item) => $item['request']['method'].' '.(is_string($item['request']['url']) ? $item['request']['url'] : $item['request']['url']['raw']));

    foreach (['GET {{base_url}}/tutores/{{tutor_id}}/bloques', 'POST {{base_url}}/tutorias', 'GET {{base_url}}/mi-agenda',
        'POST {{base_url}}/tutorias/{{tutoria_id}}/cancelar', 'POST {{base_url}}/tutorias/{{tutoria_id}}/asistencia',
        'POST {{base_url}}/tutorias/{{tutoria_id}}/inasistencia'] as $esperada) {
        expect($urls->contains(fn ($url) => str_starts_with($url, $esperada)))->toBeTrue("Falta en Postman: {$esperada}");
    }
});
