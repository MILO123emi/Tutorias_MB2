<?php

use App\Http\Controllers\Api\V1\RecuperacionController;
use App\Http\Controllers\Api\V1\RegistroController;
use App\Http\Controllers\Api\V1\SesionController;
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
});
