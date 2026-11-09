<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfil_tutor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('estado', ['PENDIENTE', 'APROBADO', 'RECHAZADO', 'INACTIVO'])
                  ->default('PENDIENTE');
            $table->text('motivo_rechazo')->nullable();
            $table->text('bio')->nullable();
            $table->string('lugar_presencial_default')->nullable();
            $table->string('enlace_virtual_default')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();

            // Campos de caché (se recalculan desde el código)
            $table->decimal('promedio_calificacion', 4, 2)->nullable();
            $table->unsignedInteger('total_calificaciones')->default(0);
            $table->unsignedInteger('total_cumplidas')->default(0);
            $table->unsignedInteger('total_no_realizadas')->default(0);

            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfil_tutor');
    }
};