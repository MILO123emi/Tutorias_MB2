<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            // null cuando el evento lo genera el sistema (por ejemplo, SIN_CONFIRMAR)
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo_evento', 50);          // TUTORIA_RESERVADA, TUTOR_APROBADO, etc.
            $table->string('entidad', 50)->nullable();  // tutorias, users, perfil_tutor...
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->text('detalle');
            $table->boolean('requiere_atencion')->default(false);
            $table->timestamp('revisado_at')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tipo_evento');
            $table->index(['requiere_atencion', 'revisado_at']);
            $table->index(['entidad', 'entidad_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};