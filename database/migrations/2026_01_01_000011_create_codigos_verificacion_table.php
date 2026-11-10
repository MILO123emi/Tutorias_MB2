<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('codigos_verificacion', function (Blueprint $table) {
            $table->id();
            $table->string('correo');
            $table->string('tipo'); // VERIFICAR_CORREO | RECUPERAR_PASS
            $table->string('codigo_hash');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_at');
            $table->timestamp('usado_at')->nullable();
            $table->timestamp('ultimo_envio_at')->nullable();
            $table->string('token_registro_hash')->nullable()->unique();
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
