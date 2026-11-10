<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('la primera foto se aplica directo', function () {
    Storage::fake('public');
    $usuario = User::factory()->create(['foto_url' => null]);
    Sanctum::actingAs($usuario);

    $this->postJson('/api/v1/perfil/foto', [
        'foto' => UploadedFile::fake()->image('yo.jpg'),
    ])->assertOk()->assertJsonStructure(['foto_url']);

    expect($usuario->fresh()->foto_url)->not->toBeNull();
    $this->assertDatabaseCount('solicitudes_foto', 0);
});

test('si ya tiene foto, la nueva queda pendiente de aprobación', function () {
    Storage::fake('public');
    $usuario = User::factory()->create(['foto_url' => '/storage/fotos/vieja.jpg']);
    Sanctum::actingAs($usuario);

    $this->postJson('/api/v1/perfil/foto', [
        'foto' => UploadedFile::fake()->image('nueva.jpg'),
    ])->assertStatus(202);

    $this->assertDatabaseHas('solicitudes_foto', [
        'user_id' => $usuario->id,
        'estado' => 'PENDIENTE',
    ]);
    // la foto anterior se mantiene
    expect($usuario->fresh()->foto_url)->toBe('/storage/fotos/vieja.jpg');
    // y se registró en la bitácora
    $this->assertDatabaseHas('bitacora', [
        'tipo_evento' => 'FOTO_SOLICITADA',
        'actor_id' => $usuario->id,
    ]);
});

test('rechaza un archivo que no es imagen', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/perfil/foto', [
        'foto' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('foto');
});

test('rechaza una imagen de más de 5 MB', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/perfil/foto', [
        'foto' => UploadedFile::fake()->image('grande.jpg')->size(6000),
    ])->assertStatus(422)->assertJsonValidationErrors('foto');
});
