<?php

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\PerfilTutor;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function estudianteConMaterias(int $cantidad = 2): array
{
    $carrera = Carrera::factory()->create();
    $user = User::factory()->create(['carrera_id' => $carrera->id]);
    $materias = Materia::factory()->count($cantidad)->create(['carrera_id' => $carrera->id]);

    return [$user, $materias];
}

function datosPostulacion(array $materias): array
{
    return [
        'bio' => '  Curso noveno semestre y ayudo en cálculo.  ',
        'lugar_presencial_default' => '  Biblioteca, mesa 4  ',
        'enlace_virtual_default' => 'https://meet.google.com/abc-defg-hij',
        'materias' => $materias,
    ];
}

test('un estudiante se postula con sus materias y genera bitácora', function () {
    [$user, $materias] = estudianteConMaterias();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/postulaciones', datosPostulacion($materias->modelKeys()))
        ->assertCreated()
        ->assertJsonPath('data.estado', 'PENDIENTE')
        ->assertJsonPath('data.bio', 'Curso noveno semestre y ayudo en cálculo.')
        ->assertJsonCount(2, 'data.materias');

    $perfil = PerfilTutor::where('user_id', $user->id)->firstOrFail();
    $this->assertDatabaseHas('perfil_tutor', ['id' => $perfil->id, 'estado' => 'PENDIENTE']);
    foreach ($materias as $materia) {
        $this->assertDatabaseHas('tutor_materias', ['tutor_id' => $user->id, 'materia_id' => $materia->id]);
    }
    $this->assertDatabaseHas('bitacora', [
        'tipo_evento' => 'TUTOR_POSTULADO',
        'entidad' => 'perfil_tutor',
        'entidad_id' => $perfil->id,
        'requiere_atencion' => true,
    ]);
});

test('los campos del servidor están prohibidos y el estado no se puede escalar', function () {
    [$user, $materias] = estudianteConMaterias();
    Sanctum::actingAs($user);
    $datos = datosPostulacion($materias->modelKeys());

    $this->postJson('/api/v1/postulaciones', [...$datos, 'estado' => 'APROBADO'])
        ->assertUnprocessable()->assertJsonValidationErrors('estado');
    $this->assertDatabaseMissing('perfil_tutor', ['user_id' => $user->id]);

    $this->postJson('/api/v1/postulaciones', $datos)->assertCreated();
    $this->assertDatabaseHas('perfil_tutor', ['user_id' => $user->id, 'estado' => 'PENDIENTE']);
});

test('no permite postular perfiles que ya están activos', function (string $estado, string $mensaje) {
    [$user, $materias] = estudianteConMaterias();
    PerfilTutor::factory()->create(['user_id' => $user->id, 'estado' => $estado]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/postulaciones', datosPostulacion($materias->modelKeys()))
        ->assertConflict()->assertJsonPath('message', $mensaje);
})->with([
    ['PENDIENTE', 'Ya tienes una postulación pendiente de revisión.'],
    ['APROBADO', 'Ya eres tutor aprobado.'],
    ['INACTIVO', 'Tu perfil de tutor está inactivo. Comunícate con Bienestar.'],
]);

test('un perfil rechazado puede volver a postularse', function () {
    [$user, $materias] = estudianteConMaterias();
    $perfil = PerfilTutor::factory()->create([
        'user_id' => $user->id,
        'estado' => 'RECHAZADO',
        'motivo_rechazo' => 'Faltó información.',
        'aprobado_por' => User::factory()->admin(),
        'fecha_aprobacion' => now(),
    ]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/postulaciones', datosPostulacion($materias->modelKeys()))
        ->assertCreated()->assertJsonPath('data.estado', 'PENDIENTE');

    $perfil->refresh();
    expect($perfil->estado)->toBe('PENDIENTE')
        ->and($perfil->motivo_rechazo)->toBeNull()
        ->and($perfil->aprobado_por)->toBeNull()
        ->and($perfil->fecha_aprobacion)->toBeNull();
});

test('un administrador no se puede postular', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/postulaciones', ['materias' => [1]])
        ->assertForbidden()->assertJsonPath('message', 'Solo los estudiantes pueden postularse como tutor.');
});

test('valida la lista de materias', function (string $caso) {
    [$user, $materias] = estudianteConMaterias();
    $otraCarrera = Carrera::factory()->create();
    $materiaAjena = Materia::factory()->create(['carrera_id' => $otraCarrera->id]);
    Sanctum::actingAs($user);

    $ids = match ($caso) {
        'vacía' => [],
        'repetida' => [$materias->first()->id, $materias->first()->id],
        default => [$materiaAjena->id],
    };

    $campo = $caso === 'vacía' ? 'materias' : 'materias.0';
    $respuesta = $this->postJson('/api/v1/postulaciones', datosPostulacion($ids))
        ->assertUnprocessable()->assertJsonValidationErrors($campo);
    if ($caso === 'otra carrera') {
        $respuesta->assertJsonFragment(['Solo puedes postularte en materias de tu carrera.']);
    }
})->with(['vacía', 'repetida', 'otra carrera']);

test('valida el enlace virtual', function () {
    [$user, $materias] = estudianteConMaterias();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/postulaciones', [
        ...datosPostulacion($materias->modelKeys()),
        'enlace_virtual_default' => 'no-es-url',
    ])->assertUnprocessable()->assertJsonValidationErrors('enlace_virtual_default');
});

test('consulta mi postulación cuando no existe', function () {
    [$user] = estudianteConMaterias();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/postulaciones/mia')->assertOk()->assertExactJson(['data' => null]);
});

test('consulta mi postulación con estado y materias', function () {
    [$user, $materias] = estudianteConMaterias();
    PerfilTutor::factory()->create(['user_id' => $user->id, 'estado' => 'PENDIENTE']);
    $user->materias()->sync($materias->modelKeys());
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/postulaciones/mia')
        ->assertOk()->assertJsonPath('data.estado', 'PENDIENTE')->assertJsonCount(2, 'data.materias');
});

test('las rutas exigen autenticación', function (string $metodo) {
    $respuesta = $metodo === 'post'
        ? $this->postJson('/api/v1/postulaciones', ['materias' => [1]])
        : $this->getJson('/api/v1/postulaciones/mia');

    $respuesta->assertUnauthorized();
})->with(['post', 'get']);

test('las cuentas suspendidas no acceden a postulaciones', function (string $metodo) {
    [$user, $materias] = estudianteConMaterias();
    $user->forceFill(['activo' => false])->save();
    Sanctum::actingAs($user);

    $respuesta = $metodo === 'post'
        ? $this->postJson('/api/v1/postulaciones', datosPostulacion($materias->modelKeys()))
        : $this->getJson('/api/v1/postulaciones/mia');

    $respuesta->assertForbidden();
})->with(['post', 'get']);
