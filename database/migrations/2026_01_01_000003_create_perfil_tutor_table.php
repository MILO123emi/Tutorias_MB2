<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('perfil_tutor', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');
            $table->string('estado')->default('PENDIENTE'); // PENDIENTE | APROBADO | RECHAZADO | INACTIVO
            $table->text('motivo_rechazo')->nullable();
            $table->text('bio')->nullable();
            $table->string('lugar_presencial_default')->nullable();
            $table->string('enlace_virtual_default')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->decimal('promedio_calificacion', 3, 2)->default(0);
            $table->unsignedInteger('total_calificaciones')->default(0);
            $table->unsignedInteger('total_cumplidas')->default(0);
            $table->unsignedInteger('total_no_realizadas')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfil_tutor');
    }
};
