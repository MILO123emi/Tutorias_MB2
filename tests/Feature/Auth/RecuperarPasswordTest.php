<?php

use App\Mail\CodigoVerificacionMail;
use App\Models\CodigoVerificacion;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('envía el código de recuperación si la cuenta existe', function () {
    Mail::fake();
    User::factory()->create(['email' => 'existe@unicah.edu']);

    $this->postJson('/api/v1/auth/recuperar/enviar-codigo', ['correo' => 'existe@unicah.edu'])
        ->assertOk();

    $this->assertDatabaseHas('codigos_verificacion', [
        'correo' => 'existe@unicah.edu',
        'tipo' => 'RECUPERAR_PASS',
    ]);
    Mail::assertQueued(CodigoVerificacionMail::class);
});

test('responde igual aunque la cuenta no exista y no envía nada', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/recuperar/enviar-codigo', ['correo' => 'nadie@unicah.edu'])
        ->assertOk();

    $this->assertDatabaseMissing('codigos_verificacion', ['correo' => 'nadie@unicah.edu']);
    Mail::assertNothingQueued();
});

test('restablece la contraseña y cierra las sesiones abiertas', function () {
    $usuario = User::factory()->create(['email' => 'existe@unicah.edu']);
    $usuario->createToken('app');

    CodigoVerificacion::create([
        'correo' => 'existe@unicah.edu',
        'tipo' => 'RECUPERAR_PASS',
        'codigo_hash' => Hash::make('123456'),
        'intentos' => 0,
        'expira_at' => now()->addMinutes(15),
        'ultimo_envio_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/recuperar/restablecer', [
        'correo' => 'existe@unicah.edu',
        'codigo' => '123456',
        'password' => 'OtraClave123',
        'password_confirmation' => 'OtraClave123',
    ])->assertOk();

    $usuario->refresh();
    expect(Hash::check('OtraClave123', $usuario->password))->toBeTrue();
    expect($usuario->tokens()->count())->toBe(0);
});
