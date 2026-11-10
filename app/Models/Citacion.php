<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citacion extends Model
{
    protected $table = 'citaciones';
    protected $fillable = ['admin_id', 'user_id', 'bitacora_id', 'tutoria_id', 'mensaje', 'fecha_cita', 'enterado_at'];

    protected $casts = [
        'fecha_cita' => 'datetime',
        'enterado_at' => 'datetime',
    ];
}
