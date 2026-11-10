<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacora';
    protected $fillable = ['actor_id', 'tipo_evento', 'entidad', 'entidad_id', 'detalle', 'requiere_atencion', 'revisado_at', 'revisado_por'];

    protected $casts = [
        'requiere_atencion' => 'boolean',
        'revisado_at' => 'datetime',
    ];
}
