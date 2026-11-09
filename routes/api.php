<?php

use App\Http\Controllers\Api\V1\RegistroController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('registro/enviar-codigo', [RegistroController::class, 'enviarCodigo'])
            ->middleware('throttle:10,1');

        Route::post('registro/verificar-codigo', [RegistroController::class, 'verificarCodigo'])
            ->middleware('throttle:20,1');
    });
});
