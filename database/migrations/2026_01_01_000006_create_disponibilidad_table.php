<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('perfil_tutor', 'user_id')->onDelete('cascade');
            $table->unsignedTinyInteger('dia_semana'); // 1=Lunes .. 6=Sábado
            $table->unsignedTinyInteger('hora_inicio'); // 7..20
            $table->string('modalidad'); // PRESENCIAL | VIRTUAL
            $table->string('lugar_o_enlace')->nullable();
            $table->timestamps();

            $table->unique(['tutor_id', 'dia_semana', 'hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidad');
    }
};
