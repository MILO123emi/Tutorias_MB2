<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_materias', function (Blueprint $table) {
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();

            $table->primary(['tutor_id', 'materia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_materias');
    }
};