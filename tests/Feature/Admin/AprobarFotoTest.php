<?php

use App\Models\Bitacora;
use App\Models\SolicitudFoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function solicitarFotoPendiente(User $usuario): SolicitudFoto
{
    Sanctum::actingAs($usuario);
    test()->postJson('/api/v1/perfil/foto', ['foto' => UploadedFile::fake()->image('nueva.jpg')])->assertStatus(202);

    return SolicitudFoto::where('user_id', $usuario->id)->latest('id')->firstOrFail();
}

test('aprobar foto aplica la nueva, cierra bitacora y notifica', function () {
    Storage::fake('public');
    $usuario = User::factory()->create(['foto_url' => '/storage/fotos/anterior.jpg']);
    $solicitud = solicitarFotoPendiente($usuario);
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/fotos/{$solicitud->id}/aprobar")->assertOk()
        ->assertJsonPath('data.estado', 'APROBADA')
        ->assertJsonPath('data.usuario.foto_actual_url', $solicitud->foto_nueva_url);
    expect($usuario->fresh()->foto_url)->toBe($solicitud->foto_nueva_url);
    $this->assertDatabaseHas('bitacora', ['tipo_evento' => 'FOTO_APROBADA', 'entidad_id' => $solicitud->id]);
    expect(Bitacora::where('tipo_evento', 'FOTO_SOLICITADA')->where('entidad_id', $solicitud->id)->first()->revisado_at)->not->toBeNull();
    $this->assertDatabaseHas('notifications', ['notifiable_id' => $usuario->id]);
});

test('rechazar foto conserva la foto actual y validar motivo', function () {
    Storage::fake('public');
    $usuario = User::factory()->create(['foto_url' => '/storage/fotos/anterior.jpg']);
    $solicitud = solicitarFotoPendiente($usuario);
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson("/api/v1/admin/fotos/{$solicitud->id}/rechazar", ['motivo' => 'corto'])->assertUnprocessable();
    $this->postJson("/api/v1/admin/fotos/{$solicitud->id}/rechazar", ['motivo' => '   La foto no muestra el rostro.  '])->assertOk()
        ->assertJsonPath('data.estado', 'RECHAZADA');
    expect($usuario->fresh()->foto_url)->toBe('/storage/fotos/anterior.jpg');
    $this->postJson("/api/v1/admin/fotos/{$solicitud->id}/aprobar")->assertConflict()
        ->assertJsonPath('message', 'Esta foto ya fue revisada.');
    $this->getJson('/api/v1/perfil/foto/solicitud')->assertOk()->assertJsonMissingPath('solicitud.usuario');
});

test('aprobar una foto cierra las otras solicitudes y conserva archivos', function () {
    Storage::fake('public');
    $usuario = User::factory()->create(['foto_url' => '/storage/fotos/anterior.jpg']);
    $primera = solicitarFotoPendiente($usuario);
    $segunda = solicitarFotoPendiente($usuario);
    $archivos = Storage::disk('public')->allFiles('fotos');
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/admin/fotos')->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.usuario.foto_actual_url', '/storage/fotos/anterior.jpg');
    $this->postJson("/api/v1/admin/fotos/{$primera->id}/aprobar")->assertOk();
    $this->assertDatabaseHas('solicitudes_foto', [
        'id' => $segunda->id, 'estado' => 'RECHAZADA',
        'motivo_rechazo' => 'Se aprobó otra foto de perfil.',
    ]);
    $this->postJson("/api/v1/admin/fotos/{$segunda->id}/aprobar")->assertConflict();
    expect(Storage::disk('public')->allFiles('fotos'))->toEqual($archivos);
});

test('aprobar una foto baja los contadores del resumen', function () {
    Storage::fake('public');
    $usuario = User::factory()->create(['foto_url' => '/storage/fotos/vieja.jpg']);
    Sanctum::actingAs($usuario);
    $this->postJson('/api/v1/perfil/foto', ['foto' => UploadedFile::fake()->image('nueva.jpg')])->assertStatus(202);

    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);
    $this->getJson('/api/v1/admin/resumen')->assertOk()
        ->assertJsonPath('data.contadores.fotos_pendientes', 1)
        ->assertJsonPath('data.contadores.eventos_sin_revisar', 1);

    $solicitud = SolicitudFoto::where('user_id', $usuario->id)->firstOrFail();
    $this->postJson("/api/v1/admin/fotos/{$solicitud->id}/aprobar")->assertOk();

    $this->getJson('/api/v1/admin/resumen')->assertOk()
        ->assertJsonPath('data.contadores.fotos_pendientes', 0)
        ->assertJsonPath('data.contadores.eventos_sin_revisar', 0);
});
