<?php

use App\Models\Carrera;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('muestra el perfil propio', function () {
    $usuario = User::factory()->create(['email' => 'yo@unicah.edu']);
    Sanctum::actingAs($usuario);

    $this->getJson('/api/v1/perfil')
        ->assertOk()
        ->assertJsonPath('usuario.email', 'yo@unicah.edu');
});

test('actualiza los datos propios sin tocar el correo', function () {
    $usuario = User::factory()->create(['email' => 'yo@unicah.edu']);
    $carrera = Carrera::factory()->create();
    Sanctum::actingAs($usuario);

    $this->putJson('/api/v1/perfil', [
        'name' => 'Nombre Nuevo',
        'telefono' => '99991234',
        'carrera_id' => $carrera->id,
        'email' => 'otro@unicah.edu',
    ])->assertOk();

    $usuario->refresh();
    expect($usuario->name)->toBe('Nombre Nuevo');
    expect($usuario->email)->toBe('yo@unicah.edu');
});

test('rechaza el perfil sin nombre', function () {
    $carrera = Carrera::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/v1/perfil', [
        'telefono' => '99991234',
        'carrera_id' => $carrera->id,
    ])->assertStatus(422)->assertJsonValidationErrors('name');
});

test('cambia la contraseña con la actual correcta', function () {
    $usuario = User::factory()->create();
    $token = $usuario->createToken('app')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/perfil/password', [
            'current_password' => 'password',
            'password' => 'ClaveNueva123',
            'password_confirmation' => 'ClaveNueva123',
        ])->assertOk();

    expect(Hash::check('ClaveNueva123', $usuario->fresh()->password))->toBeTrue();
});

test('rechaza el cambio si la contraseña actual no coincide', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $this->putJson('/api/v1/perfil/password', [
        'current_password' => 'equivocada',
        'password' => 'ClaveNueva123',
        'password_confirmation' => 'ClaveNueva123',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');
});

test('al cambiar la contraseña se cierran las otras sesiones', function () {
    $usuario = User::factory()->create();
    $otroToken = $usuario->createToken('otro-celular')->accessToken;
    $tokenActual = $usuario->createToken('este-celular')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$tokenActual)
        ->putJson('/api/v1/perfil/password', [
            'current_password' => 'password',
            'password' => 'ClaveNueva123',
            'password_confirmation' => 'ClaveNueva123',
        ])->assertOk();

    // queda vivo solo el token que hizo el cambio
    expect($usuario->tokens()->count())->toBe(1);
    expect($usuario->tokens()->whereKey($otroToken->getKey())->exists())->toBeFalse();
});
