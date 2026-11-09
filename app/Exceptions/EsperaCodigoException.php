<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

// Se lanza cuando piden otro código antes de que pasen los 60 segundos.
// Laravel llama solo a render() y responde 429 con "reintentar_en" (sección 13 del análisis).
class EsperaCodigoException extends Exception
{
    public function __construct(public int $segundos)
    {
        parent::__construct("Espera {$segundos} segundos antes de pedir otro código.");
    }

    public function render(): JsonResponse
    {
        return response()
            ->json([
                'message' => $this->getMessage(),
                'reintentar_en' => $this->segundos,
            ], 429)
            ->header('Retry-After', $this->segundos);
    }
}