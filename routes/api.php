<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\TutoriaController;
use App\Http\Controllers\Api\GestionTutoriaController;
use App\Http\Controllers\Api\AdminController;

Route::prefix('v1')->group(function () {

    // Públicas
    Route::post('auth/registro/enviar-codigo', [AuthController::class, 'enviarCodigoRegistro']);
    Route::post('auth/registro/verificar-codigo', [AuthController::class, 'verificarCodigoRegistro']);
    Route::post('auth/registro/completar', [AuthController::class, 'completarRegistro']);
    Route::post('auth/login', [AuthController::class, 'login']);

    // Protegidas por Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Estudiante y Tutor
        Route::get('tutores', [TutoriaController::class, 'indexTutores']);
        Route::get('tutores/{id}', [TutoriaController::class, 'mostrarTutorBloques']);
        Route::post('tutorias', [TutoriaController::class, 'reservar']);
        Route::get('mis-tutorias', [GestionTutoriaController::class, 'misTutorias']);
        Route::post('tutorias/{id}/cancelar', [GestionTutoriaController::class, 'cancelar']);
        Route::post('tutorias/{id}/confirmar-asistencia', [GestionTutoriaController::class, 'confirmarAsistencia']);
        Route::post('tutorias/{id}/evaluacion', [GestionTutoriaController::class, 'evaluar']);

        // Solo Tutor aprobado
        Route::middleware('role:TUTOR')->group(function () {
            Route::get('mi-disponibilidad', [DisponibilidadController::class, 'index']);
            Route::put('mi-disponibilidad', [DisponibilidadController::class, 'guardar']);
            Route::get('mi-agenda', [GestionTutoriaController::class, 'miAgenda']);
        });

        // Solo Admin (Bienestar)
        Route::middleware('role:ADMIN')->prefix('admin')->group(function () {
            Route::get('resumen', [AdminController::class, 'resumen']);
            Route::post('tutores/{id}/aprobar', [AdminController::class, 'aprobarTutor']);
            Route::get('no-realizadas', [AdminController::class, 'noRealizadas']);
            Route::post('no-realizadas/{id}/revisado', [AdminController::class, 'marcarNoRealizadaRevisada']);
            Route::get('bitacora', [AdminController::class, 'bitacora']);
        });
    });
});
