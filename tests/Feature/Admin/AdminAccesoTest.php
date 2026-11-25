<?php

use App\Models\PerfilTutor;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

dataset('rutas_admin', [
    ['GET', '/api/v1/admin/resumen'],
    ['GET', '/api/v1/admin/postulaciones'],
    ['POST', '/api/v1/admin/postulaciones/999999/aprobar'],
    ['POST', '/api/v1/admin/postulaciones/999999/rechazar'],
    ['GET', '/api/v1/admin/fotos'],
    ['POST', '/api/v1/admin/fotos/999999/aprobar'],
    ['POST', '/api/v1/admin/fotos/999999/rechazar'],
]);

test('las rutas admin requieren autenticacion', function (string $metodo, string $ruta) {
    $this->json($metodo, $ruta)->assertUnauthorized();
})->with('rutas_admin');

test('solo administradores activos acceden a las rutas admin', function (string $metodo, string $ruta) {
    Sanctum::actingAs(User::factory()->create());
    $this->json($metodo, $ruta)->assertForbidden()
        ->assertJsonPath('message', 'Necesitas permisos de administrador.');

    $tutor = User::factory()->create();
    PerfilTutor::factory()->aprobado()->create(['user_id' => $tutor->id]);
    Sanctum::actingAs($tutor);
    $this->json($metodo, $ruta)->assertForbidden()
        ->assertJsonPath('message', 'Necesitas permisos de administrador.');

    Sanctum::actingAs(User::factory()->admin()->suspendido()->create());
    $this->json($metodo, $ruta)->assertForbidden()
        ->assertJsonPath('message', 'Tu cuenta está suspendida. Comunícate con Bienestar.');

    Sanctum::actingAs(User::factory()->admin()->create());
    $respuesta = $this->json($metodo, $ruta);
    expect($respuesta->status())->not->toBeIn([401, 403]);
})->with('rutas_admin');
