<?php

namespace App\Services;

use App\Models\Parametro;
use Illuminate\Support\Facades\Cache;

class ParametroService
{
    private const PREFIJO = 'parametro:';

    /**
     * Lee un parámetro con caché. Un valor vacío ('' o null) cuenta como "sin valor"
     * y devuelve el default (por ejemplo, max_tutorias_por_dia vacío = sin límite).
     */
    public function obtener(string $clave, mixed $default = null): mixed
    {
        $valor = Cache::rememberForever(self::PREFIJO.$clave, fn () => Parametro::obtener($clave));

        return ($valor === null || $valor === '') ? $default : $valor;
    }

    /**
     * Devuelve el parámetro como entero, o el default si está vacío.
     */
    public function entero(string $clave, ?int $default = null): ?int
    {
        $valor = $this->obtener($clave);

        return $valor === null ? $default : (int) $valor;
    }

    /**
     * Borra el valor cacheado (lo usa la admin al cambiar parámetros, fase 7).
     */
    public function olvidar(string $clave): void
    {
        Cache::forget(self::PREFIJO.$clave);
    }
}
