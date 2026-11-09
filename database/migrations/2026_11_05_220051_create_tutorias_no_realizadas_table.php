<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorias_no_realizadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoria_id')->unique()->constrained('tutorias')->cascadeOnDelete();
            $table->enum('tipo', [
                'CANCELADA_TUTOR',
                'CANCELADA_ESTUDIANTE',
                'NO_ASISTIO_ESTUDIANTE',
                'NO_ASISTIO_TUTOR',
                'SIN_CONFIRMAR',
            ]);
            // null cuando es SIN_CONFIRMAR automático
            $table->foreignId('reportado_por')->nullable()->constrained('users')->nullOnDelete();

            // Propuesta de categorías (pendiente 17): ENFERMEDAD, EVENTO_ACADEMICO,
            // CHOQUE_HORARIO, EMERGENCIA, OTRO. Es string para poder cambiar la lista sin migrar.
            $table->string('categoria_motivo', 30)->nullable();
            $table->text('motivo_para_admin')->nullable();
            $table->text('motivo_para_contraparte')->nullable();

            $table->timestamp('revisado_at')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tipo');
            $table->index('revisado_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorias_no_realizadas');
    }
};