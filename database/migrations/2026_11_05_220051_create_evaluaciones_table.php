<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoria_id')->unique()->constrained('tutorias')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('users');
            $table->foreignId('tutor_id')->constrained('users');
            $table->unsignedTinyInteger('puntuacion'); // 1 a 10 (se valida en el código)
            $table->text('comentario')->nullable();    // solo lo ve la admin
            $table->timestamps();

            $table->index('tutor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};