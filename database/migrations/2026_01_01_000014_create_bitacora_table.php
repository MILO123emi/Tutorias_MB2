<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users');
            $table->string('tipo_evento');
            $table->string('entidad')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->text('detalle');
            $table->boolean('requiere_atencion')->default(false);
            $table->timestamp('revisado_at')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
