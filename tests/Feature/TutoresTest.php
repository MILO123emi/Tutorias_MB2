<?php

use App\Models\Carrera;
use App\Models\Disponibilidad;
use App\Models\Evaluacion;
use App\Models\Materia;
use App\Models\PerfilTutor;
use App\Models\User;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\MateriaSeeder;
use Database\Seeders\TutoresPruebaSeeder;
use Laravel\Sanctum\Sanctum;

function crearTutor(array $atributos = [], string $estado = 'APROBADO'): User
{
    $tutor = User::factory()->create($atributos);
    PerfilTutor::factory()->for($tutor)->create(['estado' => $estado]);

    return $tutor;
}

test('pide token para listar tutores', function () {
    $this->getJson('/api/v1/tutores')->assertUnauthorized();
});

test('por defecto lista solo los tutores aprobados de la carrera del usuario', function () {
    $carrera = Carrera::factory()->create();
    $otra = Carrera::factory()->create();
    $estudiante = User::factory()->create(['carrera_id' => $carrera->id]);

    crearTutor(['carrera_id' => $carrera->id, 'name' => 'Ana']);
    crearTutor(['carrera_id' => $carrera->id, 'name' => 'Beto']);
    crearTutor(['carrera_id' => $otra->id]);
    crearTutor(['carrera_id' => $carrera->id], 'PENDIENTE');
    crearTutor(['carrera_id' => $carrera->id, 'activo' => false]);
    Sanctum::actingAs($estudiante);

    $this->getJson('/api/v1/tutores')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Ana')
        ->assertJsonPath('data.0.carrera.id', $carrera->id)
        ->assertJsonStructure(['data' => [['id', 'name', 'foto_url', 'carrera' => ['id', 'nombre'], 'materias', 'promedio_calificacion', 'total_calificaciones', 'nuevo']], 'meta', 'links']);
});

test('carrera_id=todas quita el filtro y nunca se lista a uno mismo', function () {
    $carrera = Carrera::factory()->create();
    $yo = crearTutor(['carrera_id' => $carrera->id]);
    crearTutor(['carrera_id' => $carrera->id]);
    crearTutor(['carrera_id' => Carrera::factory()->create()->id]);
    Sanctum::actingAs($yo);

    $ids = $this->getJson('/api/v1/tutores?carrera_id=todas')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->json('data.*.id');

    expect($ids)->not->toContain($yo->id);
});

test('filtra por otra carrera, por materia y por nombre', function () {
    $carrera = Carrera::factory()->create();
    $otra = Carrera::factory()->create();
    $calculo = Materia::factory()->create(['carrera_id' => $otra->id]);

    $conCalculo = crearTutor(['carrera_id' => $otra->id, 'name' => 'Sofía Banegas']);
    $conCalculo->materias()->attach($calculo);
    crearTutor(['carrera_id' => $otra->id, 'name' => 'Mario Reyes']);
    Sanctum::actingAs(User::factory()->create(['carrera_id' => $carrera->id]));

    $this->getJson("/api/v1/tutores?carrera_id={$otra->id}")->assertJsonCount(2, 'data');
    $this->getJson("/api/v1/tutores?carrera_id=todas&materia_id={$calculo->id}")
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.materias.0.nombre', $calculo->nombre);
    $this->getJson("/api/v1/tutores?carrera_id=todas&asignatura_id={$calculo->id}")->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tutores?carrera_id=todas&buscar=reyes')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mario Reyes');
});

test('filtra por modalidad según los bloques del tutor', function () {
    $carrera = Carrera::factory()->create();
    $virtual = crearTutor(['carrera_id' => $carrera->id]);
    Disponibilidad::create(['tutor_id' => $virtual->id, 'dia_semana' => 1, 'hora_inicio' => 9, 'modalidad' => 'VIRTUAL']);
    $presencial = crearTutor(['carrera_id' => $carrera->id]);
    Disponibilidad::create(['tutor_id' => $presencial->id, 'dia_semana' => 2, 'hora_inicio' => 9, 'modalidad' => 'PRESENCIAL']);
    Sanctum::actingAs(User::factory()->create(['carrera_id' => $carrera->id]));

    $this->getJson('/api/v1/tutores?modalidad=VIRTUAL')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $virtual->id);
    $this->getJson('/api/v1/tutores?modalidad=OTRA')->assertStatus(422);
});

test('devuelve el promedio sobre 5 y marca como nuevo con menos de 3 calificaciones', function () {
    $carrera = Carrera::factory()->create();
    $conPromedio = User::factory()->create(['carrera_id' => $carrera->id, 'name' => 'A']);
    PerfilTutor::factory()->aprobado()->conCalificaciones(4.3, 8)->for($conPromedio)->create();
    $nuevo = User::factory()->create(['carrera_id' => $carrera->id, 'name' => 'B']);
    PerfilTutor::factory()->aprobado()->conCalificaciones(5, 2)->for($nuevo)->create();
    Sanctum::actingAs(User::factory()->create(['carrera_id' => $carrera->id]));

    $this->getJson('/api/v1/tutores')
        ->assertJsonPath('data.0.promedio_calificacion', 4.5)
        ->assertJsonPath('data.0.total_calificaciones', 8)
        ->assertJsonPath('data.0.nuevo', false)
        ->assertJsonPath('data.1.nuevo', true);
});

test('pagina de a 15 tutores', function () {
    $carrera = Carrera::factory()->create();
    foreach (range(1, 17) as $i) {
        crearTutor(['carrera_id' => $carrera->id]);
    }
    Sanctum::actingAs(User::factory()->create(['carrera_id' => $carrera->id]));

    $this->getJson('/api/v1/tutores')->assertJsonCount(15, 'data')->assertJsonPath('meta.total', 17);
    $this->getJson('/api/v1/tutores?page=2')->assertJsonCount(2, 'data');
});

test('muestra el perfil de un tutor con sus materias y sin comentarios', function () {
    $carrera = Carrera::factory()->create();
    $tutor = crearTutor(['carrera_id' => $carrera->id]);
    $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
    $tutor->materias()->attach($materia);
    Sanctum::actingAs(User::factory()->create(['carrera_id' => $carrera->id]));

    $this->getJson("/api/v1/tutores/{$tutor->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $tutor->id)
        ->assertJsonPath('data.carrera.nombre', $carrera->nombre)
        ->assertJsonPath('data.materias.0.id', $materia->id)
        ->assertJsonStructure(['data' => ['bio', 'total_cumplidas', 'foto_url']])
        ->assertJsonMissingPath('data.comentarios')
        ->assertJsonMissingPath('data.telefono');
});

test('el perfil de alguien que no es tutor aprobado da 404', function () {
    $pendiente = crearTutor([], 'PENDIENTE');
    $sinPerfil = User::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/tutores/{$pendiente->id}")->assertNotFound();
    $this->getJson("/api/v1/tutores/{$sinPerfil->id}")->assertNotFound();
});

test('el seeder de tutores de prueba crea tutores aprobados en la carrera del estudiante', function () {
    $this->seed([CarreraSeeder::class, MateriaSeeder::class]);
    $carrera = Carrera::orderBy('id')->skip(2)->first();
    $estudiante = User::factory()->create(['email' => 'estudiante@unicah.edu', 'carrera_id' => $carrera->id]);

    app()['env'] = 'local';
    $this->seed(TutoresPruebaSeeder::class);
    $this->seed(TutoresPruebaSeeder::class); // se puede correr dos veces sin duplicar

    expect(PerfilTutor::where('estado', 'APROBADO')->count())->toBe(9)
        ->and(User::where('carrera_id', $carrera->id)->whereHas('perfilTutor')->count())->toBe(4)
        ->and($estudiante->tutoriasComoEstudiante()->count())->toBe(5)
        ->and(Evaluacion::count())->toBe(0);

    Sanctum::actingAs($estudiante);
    $this->getJson('/api/v1/tutores')->assertJsonCount(4, 'data');
    $this->getJson('/api/v1/tutores?carrera_id=todas')->assertJsonCount(9, 'data');
});
