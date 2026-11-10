<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('citaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('bitacora_id')->nullable();
            $table->unsignedBigInteger('tutoria_id')->nullable();
            $table->text('mensaje');
            $table->timestamp('fecha_cita')->nullable();
            $table->timestamp('enterado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citaciones');
    }
};
