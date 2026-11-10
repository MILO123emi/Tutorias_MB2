<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
            $table->unsignedTinyInteger('hora_inicio'); // 7..20
            $table->string('modalidad'); // PRESENCIAL | VIRTUAL
            $table->string('lugar_o_enlace');
            $table->string('estado')->default('CONFIRMADA'); // CONFIRMADA | COMPLETADA | CANCELADA_TUTOR | CANCELADA_ESTUDIANTE | NO_ASISTIO_ESTUDIANTE | NO_ASISTIO_TUTOR | SIN_CONFIRMAR
            $table->timestamp('asistencia_confirmada_at')->nullable();
            $table->timestamps();

            $table->index(['fecha', 'tutor_id', 'estado']);
            $table->index(['fecha', 'estudiante_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorias');
    }
};
