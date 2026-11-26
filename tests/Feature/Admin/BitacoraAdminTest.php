<?php

use App\Models\Bitacora;
use App\Models\Parametro;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('registra la creacion, el cambio de rol y la suspension de una cuenta', function () {
    $admin = User::factory()->admin()->create();
    $usuario = User::factory()->create();
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'USUARIO_REGISTRADO', 'entidad_id' => $usuario->id]);

    Sanctum::actingAs($admin);
    $usuario->forceFill(['rol' => 'ADMIN'])->save();
    $usuario->forceFill(['activo' => false])->save();
    $usuario->forceFill(['telefono' => '99998888'])->save();

    $this->assertDatabaseHas('bitacora', [
        'tipo_evento' => 'USUARIO_ROL_CAMBIADO', 'actor_id' => $admin->id,
        'entidad_id' => $usuario->id, 'requiere_atencion' => true,
    ]);
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'USUARIO_SUSPENDIDO', 'entidad_id' => $usuario->id]);
    // Un cambio de datos normales no ensucia la bitácora
    expect(Bitacora::where('entidad', 'users')->where('entidad_id', $usuario->id)->count())->toBe(3);
});

test('registra el cambio de un parametro', function () {
    $parametro = Parametro::create(['clave' => 'max_tutorias_por_dia', 'valor' => '3']);
    $this->assertDatabaseMissing('bitacora', ['tipo_evento' => 'PARAMETRO_ACTUALIZADO']);

    $parametro->update(['valor' => '5']);

    expect(Bitacora::where('tipo_evento', 'PARAMETRO_ACTUALIZADO')->value('detalle'))
        ->toBe('El parámetro max_tutorias_por_dia cambió de 3 a 5.');
});

test('admin consulta la bitacora con filtros', function () {
    $admin = User::factory()->admin()->create();
    $actor = User::factory()->create();
    Bitacora::query()->delete();

    Bitacora::create(['actor_id' => $actor->id, 'tipo_evento' => 'INASISTENCIA_REPORTADA', 'entidad' => 'tutorias', 'entidad_id' => 7, 'detalle' => 'A', 'requiere_atencion' => true]);
    Bitacora::create(['tipo_evento' => 'TUTORIA_SIN_CONFIRMAR', 'entidad' => 'tutorias', 'entidad_id' => 8, 'detalle' => 'B', 'requiere_atencion' => true])
        ->forceFill(['revisado_at' => now(), 'revisado_por' => $admin->id])->save();
    Bitacora::create(['actor_id' => $actor->id, 'tipo_evento' => 'TUTORIA_RESERVADA', 'entidad' => 'tutorias', 'entidad_id' => 9, 'detalle' => 'C']);
    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/bitacora')
        ->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.0.tipo_evento', 'TUTORIA_RESERVADA');
    $this->getJson('/api/v1/admin/bitacora?pendientes=1')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.actor.id', $actor->id);
    $this->getJson('/api/v1/admin/bitacora?requiere_atencion=1')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/admin/bitacora?tipo_evento=TUTORIA_SIN_CONFIRMAR')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.actor', null)
        ->assertJsonPath('data.0.revisado_por.id', $admin->id);
    $this->getJson("/api/v1/admin/bitacora?actor_id={$actor->id}")->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/admin/bitacora?entidad=tutorias&entidad_id=9')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/admin/bitacora?desde='.now()->addDay()->toDateString())->assertOk()->assertJsonCount(0, 'data');
});
