<?php

use App\Models\Bitacora;
use App\Models\PerfilTutor;
use App\Models\SolicitudFoto;
use App\Models\Tutoria;
use App\Models\TutoriaNoRealizada;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->admin()->create());
});

test('el resumen vacio contiene contadores en cero', function () {
    $this->getJson('/api/v1/admin/resumen')->assertOk()
        ->assertJsonPath('data.contadores.tutorias_hoy', 0)
        ->assertJsonPath('data.contadores.no_realizadas_sin_revisar', 0)
        ->assertJsonPath('data.contadores.eventos_sin_revisar', 0)
        ->assertJsonPath('data.contadores.postulaciones_pendientes', 0)
        ->assertJsonPath('data.contadores.fotos_pendientes', 0)
        ->assertJsonPath('data.contadores.aprobaciones_pendientes', 0)
        ->assertExactJsonStructure(['data' => ['fecha', 'contadores', 'tutorias_hoy']]);
    expect($this->getJson('/api/v1/admin/resumen')->json('data.tutorias_hoy'))->toBe([]);
});

test('cuenta y ordena todas las tutorias de hoy con contactos', function () {
    $hoy = CarbonImmutable::now(config('app.timezone'))->toDateString();
    $tutorias = collect([12, 9, 15])->map(fn ($hora, $i) => Tutoria::factory()->create([
        'fecha' => $hoy, 'hora_inicio' => $hora,
        'estado' => ['CONFIRMADA', 'COMPLETADA', 'CANCELADA_ESTUDIANTE'][$i],
        'tutor_id' => User::factory()->create(['telefono' => '99998888'])->id,
        'estudiante_id' => User::factory()->create(['telefono' => '88887777'])->id,
    ]));
    Tutoria::factory()->create(['fecha' => CarbonImmutable::parse($hoy)->addDay(), 'hora_inicio' => 7]);

    $respuesta = $this->getJson('/api/v1/admin/resumen')->assertOk();
    $respuesta->assertJsonPath('data.contadores.tutorias_hoy', 3)
        ->assertJsonCount(3, 'data.tutorias_hoy')
        ->assertJsonPath('data.tutorias_hoy.0.hora_inicio', 9)
        ->assertJsonPath('data.tutorias_hoy.1.hora_inicio', 12)
        ->assertJsonPath('data.tutorias_hoy.0.tutor.telefono', '99998888')
        ->assertJsonPath('data.tutorias_hoy.0.estudiante.telefono', '88887777');
});

test('cuenta pendientes de postulacion foto bitacora y no realizadas', function () {
    PerfilTutor::factory()->count(2)->create(['estado' => 'PENDIENTE']);
    PerfilTutor::factory()->create(['estado' => 'APROBADO']);
    SolicitudFoto::factory()->create();
    Bitacora::create(['tipo_evento' => 'A', 'requiere_atencion' => true, 'detalle' => 'A']);
    Bitacora::create(['tipo_evento' => 'B', 'requiere_atencion' => true, 'detalle' => 'B'])->forceFill(['revisado_at' => now()])->save();
    Bitacora::create(['tipo_evento' => 'C', 'requiere_atencion' => false, 'detalle' => 'C']);
    $t1 = Tutoria::factory()->create();
    $t2 = Tutoria::factory()->create();
    TutoriaNoRealizada::create(['tutoria_id' => $t1->id, 'tipo' => 'CANCELADA_TUTOR']);
    TutoriaNoRealizada::create(['tutoria_id' => $t2->id, 'tipo' => 'CANCELADA_TUTOR'])->forceFill(['revisado_at' => now()])->save();
    // Las factorías pueden disparar eventos de dominio; aquí aislamos los tres casos del contador.
    Bitacora::query()->delete();
    Bitacora::create(['tipo_evento' => 'A', 'requiere_atencion' => true, 'detalle' => 'A']);
    Bitacora::create(['tipo_evento' => 'B', 'requiere_atencion' => true, 'detalle' => 'B'])->forceFill(['revisado_at' => now()])->save();
    Bitacora::create(['tipo_evento' => 'C', 'requiere_atencion' => false, 'detalle' => 'C']);

    $this->getJson('/api/v1/admin/resumen')->assertOk()
        ->assertJsonPath('data.contadores.postulaciones_pendientes', 2)
        ->assertJsonPath('data.contadores.fotos_pendientes', 1)
        ->assertJsonPath('data.contadores.aprobaciones_pendientes', 3)
        ->assertJsonPath('data.contadores.eventos_sin_revisar', 1)
        ->assertJsonPath('data.contadores.no_realizadas_sin_revisar', 1);
});

test('usa la fecha de Tegucigalpa al consultar el resumen', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-24 23:30', 'America/Tegucigalpa'));
    Tutoria::factory()->create(['fecha' => '2026-11-24', 'hora_inicio' => 9]);

    $this->getJson('/api/v1/admin/resumen')->assertOk()
        ->assertJsonPath('data.fecha', '2026-11-24')
        ->assertJsonPath('data.contadores.tutorias_hoy', 1);
});
