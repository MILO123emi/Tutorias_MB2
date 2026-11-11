<?php

use App\Http\Controllers\Auth\RegistroController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Registro por código (correo → código → completar perfil); reemplaza al registro del kit.
Route::middleware('guest')->group(function () {
    Route::get('registro', [RegistroController::class, 'mostrarCorreo'])->name('registro');
    Route::post('registro/enviar-codigo', [RegistroController::class, 'enviarCodigo'])->name('registro.enviar-codigo');
    Route::get('registro/codigo', [RegistroController::class, 'mostrarCodigo'])->name('registro.codigo');
    Route::post('registro/reenviar-codigo', [RegistroController::class, 'reenviarCodigo'])->name('registro.reenviar-codigo');
    Route::post('registro/verificar-codigo', [RegistroController::class, 'verificarCodigo'])->name('registro.verificar-codigo');
    Route::get('registro/completar', [RegistroController::class, 'mostrarCompletar'])->name('registro.completar');
    Route::post('registro/completar', [RegistroController::class, 'completar'])->name('registro.completar.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
