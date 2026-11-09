<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_verificacion', function (Blueprint $table) {
            $table->id();
            // Se busca por correo y no por usuario: al registrarse, el usuario todavía no existe
            $table->string('correo');
            $table->enum('tipo', ['VERIFICAR_CORREO', 'RECUPERAR_PASS']);
            $table->string('codigo_hash');                 // nunca se guarda el código en claro
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_at');
            $table->timestamp('usado_at')->nullable();
            $table->timestamp('ultimo_envio_at')->nullable();

            // Token que se entrega al verificar el código (30 min, un solo uso)
            $table->string('token_registro_hash', 64)->nullable()->unique();
            $table->timestamp('token_registro_expira_at')->nullable();
            $table->timestamps();

            $table->unique(['correo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_verificacion');
    }
};