<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('ESTUDIANTE'); // ESTUDIANTE | ADMIN
            $table->foreignId('carrera_id')->nullable()->constrained('carreras')->nullOnDelete();
            $table->string('telefono')->nullable();
            $table->string('foto_url')->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['carrera_id']);
            $table->dropColumn(['rol', 'carrera_id', 'telefono', 'foto_url', 'activo']);
        });
    }
};
