<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametro extends Model
{
    protected $table = 'parametros';

    // La llave primaria es 'clave' (texto), no un id numérico
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor'];

    // Atajo para leer un parámetro: Parametro::obtener('max_tutorias_por_dia', null)
    public static function obtener(string $clave, mixed $default = null): mixed
    {
        $valor = static::query()->whereKey($clave)->value('valor');

        return ($valor === null || $valor === '') ? $default : $valor;
    }
}