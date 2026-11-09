<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users');   // la persona citada
            $table->foreignId('bitacora_id')->nullable()->constrained('bitacora')->nullOnDelete();
            $table->foreignId('tutoria_id')->nullable()->constrained('tutorias')->nullOnDelete();
            $table->text('mensaje');
            $table->dateTime('fecha_cita')->nullable();
            $table->timestamp('enterado_at')->nullable();         // acuse de "Enterado"
            $table->timestamps();

            $table->index(['user_id', 'enterado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citaciones');
    }
};