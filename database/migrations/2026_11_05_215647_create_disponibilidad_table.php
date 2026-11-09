<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');   // 1 = lunes ... 6 = sábado
            $table->unsignedTinyInteger('hora_inicio');  // 7 a 20
            $table->enum('modalidad', ['PRESENCIAL', 'VIRTUAL']);
            $table->string('lugar_o_enlace')->nullable(); // si está vacío se usa el default del tutor
            $table->timestamps();

            $table->unique(['tutor_id', 'dia_semana', 'hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidad');
    }
};