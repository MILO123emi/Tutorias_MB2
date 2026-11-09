<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['ESTUDIANTE', 'ADMIN'])->default('ESTUDIANTE')->after('password');
            $table->foreignId('carrera_id')->nullable()->after('rol')
                  ->constrained('carreras')->nullOnDelete();
            $table->string('telefono', 20)->nullable()->after('carrera_id');
            $table->string('foto_url')->nullable()->after('telefono');
            $table->boolean('activo')->default(true)->after('foto_url');
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