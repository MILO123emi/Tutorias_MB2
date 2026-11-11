<?php

use App\Http\Controllers\Api\V1\CarreraController;
use App\Http\Controllers\Api\V1\CitacionController;
use App\Http\Controllers\Api\V1\DispositivoController;
use App\Http\Controllers\Api\V1\MateriaController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PerfilController;
use App\Http\Controllers\Api\V1\PerfilTutorController;
use App\Http\Controllers\Api\V1\RecuperacionController;
use App\Http\Controllers\Api\V1\RegistroController;
use App\Http\Controllers\Api\V1\SesionController;
use App\Http\Controllers\Api\V1\TutorController;
use App\Http\Controllers\Api\V1\TutoriaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        // ---------- Sin token ----------
        Route::post('registro/enviar-codigo', [RegistroController::class, 'enviarCodigo'])
            ->middleware('throttle:10,1');
        Route::post('registro/verificar-codigo', [RegistroController::class, 'verificarCodigo'])
            ->middleware('throttle:20,1');
        Route::post('registro/completar', [RegistroController::class, 'completar'])
            ->middleware('throttle:10,1');

        Route::post('recuperar/enviar-codigo', [RecuperacionController::class, 'enviarCodigo'])
            ->middleware('throttle:10,1');
        Route::post('recuperar/restablecer', [RecuperacionController::class, 'restablecer'])
            ->middleware('throttle:20,1');

        Route::post('login', [SesionController::class, 'login'])
            ->middleware('throttle:10,1');

        // ---------- Con token ----------
        Route::middleware(['auth:sanctum', 'cuenta.activa'])->group(function () {
            Route::post('logout', [SesionController::class, 'logout']);
            Route::get('yo', [SesionController::class, 'yo']);
        });
    });

    // ---------- Cualquier rol (con token) ----------
    Route::middleware(['auth:sanctum', 'cuenta.activa'])->group(function () {
        // Catálogos
        Route::get('carreras', [CarreraController::class, 'index']);
        Route::get('materias', [MateriaController::class, 'index']);

        // Perfil
        Route::get('perfil', [PerfilController::class, 'show']);
        Route::put('perfil', [PerfilController::class, 'update']);
        Route::put('perfil/password', [PerfilController::class, 'updatePassword']);
        Route::post('perfil/foto', [PerfilController::class, 'subirFoto']);
        Route::get('perfil/foto/solicitud', [PerfilController::class, 'solicitudFoto']);

        // Notificaciones (campanita)
        Route::get('notificaciones', [NotificacionController::class, 'index']);
        Route::get('notificaciones/no-leidas', [NotificacionController::class, 'noLeidas']);
        Route::post('notificaciones/leer-todas', [NotificacionController::class, 'leerTodas']);
        Route::post('notificaciones/{id}/leida', [NotificacionController::class, 'leida']);

        // Citaciones del usuario
        Route::get('citaciones', [CitacionController::class, 'index']);
        Route::post('citaciones/{id}/enterado', [CitacionController::class, 'enterado']);

        // Dispositivos para push (FCM)
        Route::post('dispositivos', [DispositivoController::class, 'store']);
        Route::delete('dispositivos', [DispositivoController::class, 'destroy']);

        // ---------- Módulo de Tutores ----------
        Route::get('tutores', [TutorController::class, 'index']);
        Route::post('perfil-tutor/solicitar', [PerfilTutorController::class, 'store']);
        Route::get('perfil-tutor/mi-estado', [PerfilTutorController::class, 'miEstado']);
        Route::post('perfil-tutor/materias', [PerfilTutorController::class, 'sincronizarMaterias']);
        Route::get('perfil-tutor/materias', [PerfilTutorController::class, 'obtenerMaterias']);

        // ---------- Módulo de Tutorías / Secciones ----------
        Route::post('tutor/secciones', [TutoriaController::class, 'store']);
        Route::get('tutor/secciones', [TutoriaController::class, 'index']);
    });
});

