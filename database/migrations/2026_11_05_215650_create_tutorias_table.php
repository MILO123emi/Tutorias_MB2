<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('users');
            $table->foreignId('tutor_id')->constrained('users');
            $table->foreignId('materia_id')->constrained('materias');
            $table->string('tema');
            $table->text('descripcion')->nullable();

            $table->date('fecha');
            $table->unsignedTinyInteger('hora_inicio'); // 7 a 20
            $table->enum('modalidad', ['PRESENCIAL', 'VIRTUAL']);   // se copia del bloque al reservar
            $table->string('lugar_o_enlace')->nullable();           // se copia del bloque al reservar

            $table->enum('estado', [
                'CONFIRMADA',
                'COMPLETADA',
                'NO_ASISTIO_ESTUDIANTE',
                'NO_ASISTIO_TUTOR',
                'CANCELADA_TUTOR',
                'CANCELADA_ESTUDIANTE',
                'SIN_CONFIRMAR',
            ])->default('CONFIRMADA');
            $table->timestamp('asistencia_confirmada_at')->nullable();

            // Columna generada: vale 1 mientras la tutoría ocupa el bloque, y NULL si no.
            // Varios NULL conviven en un índice único, así que solo chocan las activas.
            // CASE WHEN funciona igual en MySQL y en SQLite.
            $table->unsignedTinyInteger('slot_activo')->nullable()
                  ->virtualAs("CASE WHEN estado IN ('CONFIRMADA', 'COMPLETADA') THEN 1 END");

            $table->timestamps();

            // Anti doble reserva (RN-06 y RN-07)
            $table->unique(['tutor_id', 'fecha', 'hora_inicio', 'slot_activo'], 'uq_tutoria_tutor_slot');
            $table->unique(['estudiante_id', 'fecha', 'hora_inicio', 'slot_activo'], 'uq_tutoria_estudiante_slot');

            // Índices para listados y reportes (RNF-02)
            $table->index('fecha');
            $table->index(['tutor_id', 'estado']);
            $table->index(['estudiante_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorias');
    }
};