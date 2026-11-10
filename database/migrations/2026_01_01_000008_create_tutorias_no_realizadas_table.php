<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tutorias_no_realizadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoria_id')->unique()->constrained('tutorias')->onDelete('cascade');
            $table->string('tipo'); // CANCELADA_TUTOR | CANCELADA_ESTUDIANTE | NO_ASISTIO_ESTUDIANTE | NO_ASISTIO_TUTOR | SIN_CONFIRMAR
            $table->foreignId('reportado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('categoria_motivo')->nullable(); // ENFERMEDAD | EVENTO_ACADEMICO | CHOQUE_HORARIO | EMERGENCIA | OTRO
            $table->text('motivo_para_admin')->nullable();
            $table->text('motivo_para_contraparte')->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorias_no_realizadas');
    }
};
