<?php

use App\Models\Bitacora;
use App\Models\Citacion;
use App\Models\Tutoria;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('admin cita a un usuario por un evento de la bitacora', function () {
    $admin = User::factory()->admin()->create();
    $tutoria = Tutoria::factory()->estado('NO_ASISTIO_TUTOR')->create();
    $evento = Bitacora::create([
        'actor_id' => $tutoria->estudiante_id, 'tipo_evento' => 'INASISTENCIA_REPORTADA',
        'entidad' => 'tutorias', 'entidad_id' => $tutoria->id, 'detalle' => 'Faltó', 'requiere_atencion' => true,
    ]);
    Sanctum::actingAs($admin);

    $respuesta = $this->postJson('/api/v1/admin/citaciones', [
        'user_id' => $tutoria->tutor_id,
        'mensaje' => '  Preséntate en Bienestar Estudiantil para hablar de la tutoría.  ',
        'fecha_cita' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'tutoria_id' => $tutoria->id,
        'bitacora_id' => $evento->id,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.citado.id', $tutoria->tutor_id)
        ->assertJsonPath('data.mensaje', 'Preséntate en Bienestar Estudiantil para hablar de la tutoría.')
        ->assertJsonPath('data.enterado', false);
    $this->assertDatabaseHas('citaciones', ['admin_id' => $admin->id, 'user_id' => $tutoria->tutor_id, 'tutoria_id' => $tutoria->id]);
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'CITACION_CREADA', 'actor_id' => $admin->id, 'entidad_id' => $respuesta->json('data.id')]);
    $this->assertDatabaseHas('notifications', ['notifiable_id' => $tutoria->tutor_id]);
    expect($evento->fresh()->revisado_por)->toBe($admin->id);

    Sanctum::actingAs(User::find($tutoria->tutor_id));
    $this->getJson('/api/v1/citaciones')->assertOk()->assertJsonCount(1, 'data');
});

test('valida los datos de la citacion', function () {
    $admin = User::factory()->admin()->create();
    $usuario = User::factory()->create();
    $tutoriaAjena = Tutoria::factory()->create();
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/admin/citaciones', ['user_id' => $admin->id, 'mensaje' => 'Mensaje suficientemente largo.'])
        ->assertUnprocessable()->assertJsonValidationErrors(['user_id' => 'No puedes citarte a ti mismo.']);
    $this->postJson('/api/v1/admin/citaciones', ['user_id' => $usuario->id, 'mensaje' => 'corto'])
        ->assertUnprocessable()->assertJsonValidationErrors('mensaje');
    $this->postJson('/api/v1/admin/citaciones', [
        'user_id' => $usuario->id, 'mensaje' => 'Mensaje suficientemente largo.', 'fecha_cita' => now()->subDay()->toDateString(),
    ])->assertUnprocessable()->assertJsonValidationErrors('fecha_cita');
    $this->postJson('/api/v1/admin/citaciones', [
        'user_id' => $usuario->id, 'mensaje' => 'Mensaje suficientemente largo.', 'tutoria_id' => $tutoriaAjena->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['tutoria_id' => 'La persona citada no participa en esa tutoría.']);
    $this->postJson('/api/v1/admin/citaciones', [
        'user_id' => $usuario->id, 'mensaje' => 'Mensaje suficientemente largo.', 'admin_id' => $usuario->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('admin_id');

    expect(Citacion::count())->toBe(0);
});
