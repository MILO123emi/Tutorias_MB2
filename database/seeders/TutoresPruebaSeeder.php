<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Disponibilidad;
use App\Models\Materia;
use App\Models\PerfilTutor;
use App\Models\Tutoria;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Tutores aprobados para probar la búsqueda en desarrollo.
 * Uso: php artisan db:seed --class=TutoresPruebaSeeder
 * Necesita las carreras y materias cargadas (CarreraSeeder y MateriaSeeder).
 */
class TutoresPruebaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Carrera::count() === 0 || Materia::count() === 0) {
            $this->command?->warn('Primero cargá las carreras y materias (CarreraSeeder y MateriaSeeder).');

            return;
        }

        $estudiante = User::where('email', env('ESTUDIANTE_PRUEBA_EMAIL', 'estudiante@unicah.edu'))->first();
        $carreraEstudiante = $estudiante?->carrera_id ?? Carrera::orderBy('id')->value('id');
        $otrasCarreras = Carrera::whereKeyNot($carreraEstudiante)->orderBy('id')->pluck('id')->all();

        // nombre, correo, carrera (null = la del estudiante de prueba), promedio, calificaciones, bio
        $tutores = [
            ['Ana Lucía Martínez', 'ana.martinez', null, 4.5, 12, 'Me gusta explicar con ejemplos y ejercicios resueltos paso a paso.'],
            ['Carlos Eduardo Reyes', 'carlos.reyes', null, 4.0, 7, 'Repasamos la teoría y después practicamos con exámenes anteriores.'],
            ['María Fernanda López', 'maria.lopez', null, null, 1, 'Primera vez como tutora; tengo buen promedio en las materias que ofrezco.'],
            ['José Andrés Mejía', 'jose.mejia', null, 3.5, 4, 'Enfocado en resolver dudas puntuales antes de los parciales.'],
            ['Daniela Sofía Banegas', 'daniela.banegas', 0, 5.0, 9, 'Tutorías tranquilas, a tu ritmo.'],
            ['Luis Fernando Castillo', 'luis.castillo', 1, 4.5, 15, 'Trabajo con mapas conceptuales y resúmenes.'],
            ['Gabriela Alejandra Flores', 'gabriela.flores', 2, null, 0, 'Recién aprobada como tutora.'],
            ['Kevin Josué Hernández', 'kevin.hernandez', 3, 4.0, 6, 'Prefiero las sesiones virtuales con pizarra compartida.'],
            ['Valeria Nicole Zelaya', 'valeria.zelaya', 4, 3.5, 3, 'Te ayudo a organizar el estudio de toda la clase.'],
        ];

        $creados = [];

        foreach ($tutores as $i => [$nombre, $usuarioCorreo, $indiceCarrera, $promedio, $total, $bio]) {
            $carreraId = $indiceCarrera === null
                ? $carreraEstudiante
                : ($otrasCarreras[$indiceCarrera % max(count($otrasCarreras), 1)] ?? $carreraEstudiante);

            $tutor = User::firstOrNew(['email' => "{$usuarioCorreo}@unicah.edu"]);
            if (! $tutor->exists) {
                $tutor->password = env('ESTUDIANTE_PRUEBA_PASSWORD') ?: Str::random(24);
            }
            // 'rol' y 'activo' no están en $fillable, por eso se asignan directo.
            $tutor->name = $nombre;
            $tutor->rol = 'ESTUDIANTE';
            $tutor->activo = true;
            $tutor->carrera_id = $carreraId;
            $tutor->telefono = $tutor->telefono ?? sprintf('9%07d', 1000 + $i);
            $tutor->email_verified_at = $tutor->email_verified_at ?? now();
            $tutor->save();

            $perfil = PerfilTutor::firstOrNew(['user_id' => $tutor->id]);
            $perfil->bio = $bio;
            $perfil->lugar_presencial_default = 'Biblioteca, segundo piso';
            $perfil->enlace_virtual_default = 'https://meet.google.com/';
            // Estado y campos de caché no son fillable: los fija el servidor.
            $perfil->estado = 'APROBADO';
            $perfil->fecha_aprobacion = $perfil->fecha_aprobacion ?? now();
            $perfil->promedio_calificacion = $promedio;
            $perfil->total_calificaciones = $total;
            $perfil->total_cumplidas = $total;
            $perfil->save();

            // Tres materias de su carrera, distintas para cada tutor.
            $materias = Materia::where('carrera_id', $carreraId)
                ->orderBy('id')
                ->skip(($i * 3) % 12)
                ->take(3)
                ->pluck('id');
            $tutor->materias()->sync($materias);

            // Bloques semanales: los pares presenciales y los impares virtuales.
            foreach ([[1, 8 + $i % 4], [3, 14], [5, 10 + $i % 3]] as $j => [$dia, $hora]) {
                Disponibilidad::updateOrCreate(
                    ['tutor_id' => $tutor->id, 'dia_semana' => $dia, 'hora_inicio' => $hora],
                    ['modalidad' => ($i + $j) % 2 === 0 ? 'PRESENCIAL' : 'VIRTUAL'],
                );
            }

            $creados[] = $tutor;
        }

        $this->crearTutoriasDelEstudiante($estudiante, $creados);

        $this->command?->info(count($creados).' tutores de prueba listos.');
    }

    /**
     * Unas tutorías de ejemplo para "Mis tutorías". Solo se crean si el estudiante
     * todavía no tiene ninguna, para no duplicarlas al volver a correr el seeder.
     *
     * @param  array<int, User>  $tutores
     */
    private function crearTutoriasDelEstudiante(?User $estudiante, array $tutores): void
    {
        if ($estudiante === null || $estudiante->tutoriasComoEstudiante()->exists()) {
            return;
        }

        $deSuCarrera = array_values(array_filter($tutores, fn (User $t) => (int) $t->carrera_id === (int) $estudiante->carrera_id));
        if (count($deSuCarrera) < 2) {
            return;
        }

        $ejemplos = [
            // tutor, días desde hoy, hora, estado, tema
            [0, 2, 10, 'CONFIRMADA', 'Repaso para el primer parcial'],
            [1, 4, 14, 'CONFIRMADA', 'Ejercicios de práctica'],
            [0, -6, 9, 'COMPLETADA', 'Dudas de la tarea'],
            [1, -10, 15, 'CANCELADA_ESTUDIANTE', 'Conceptos básicos'],
            [0, -15, 11, 'NO_ASISTIO_TUTOR', 'Preparación de exposición'],
        ];

        foreach ($ejemplos as [$indice, $dias, $hora, $estado, $tema]) {
            $tutor = $deSuCarrera[$indice];
            $materiaId = $tutor->materias()->value('materias.id');
            if ($materiaId === null) {
                continue;
            }

            $fecha = Carbon::today()->addDays($dias);
            if ($fecha->isSunday()) {
                $fecha->addDay();
            }

            $tutoria = new Tutoria([
                'estudiante_id' => $estudiante->id,
                'tutor_id' => $tutor->id,
                'materia_id' => $materiaId,
                'tema' => $tema,
                'descripcion' => 'Tutoría de ejemplo cargada por el seeder.',
                'fecha' => $fecha->toDateString(),
                'hora_inicio' => $hora,
                'modalidad' => $dias % 2 === 0 ? 'PRESENCIAL' : 'VIRTUAL',
                'lugar_o_enlace' => $dias % 2 === 0 ? 'Biblioteca, segundo piso' : 'https://meet.google.com/',
            ]);
            // El estado lo maneja el servidor; no es fillable.
            $tutoria->estado = $estado;
            $tutoria->save();
        }
    }
}
