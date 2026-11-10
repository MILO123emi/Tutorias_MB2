<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoVerificacion extends Model
{
    protected $table = 'codigos_verificacion';
    protected $fillable = [
        'correo', 'tipo', 'codigo_hash', 'intentos', 'expira_at',
        'usado_at', 'ultimo_envio_at', 'token_registro_hash', 'token_registro_expira_at'
    ];

    protected $casts = [
        'expira_at' => 'datetime',
        'usado_at' => 'datetime',
        'ultimo_envio_at' => 'datetime',
        'token_registro_expira_at' => 'datetime',
    ];
}
