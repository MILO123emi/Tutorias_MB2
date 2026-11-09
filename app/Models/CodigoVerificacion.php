<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoVerificacion extends Model
{
    protected $table = 'codigos_verificacion';

    // Este modelo solo lo usa el CodigoVerificacionService, nunca recibe datos directos de una petición.
    protected $fillable = [
        'correo',
        'tipo',
        'codigo_hash',
        'intentos',
        'expira_at',
        'usado_at',
        'ultimo_envio_at',
        'token_registro_hash',
        'token_registro_expira_at',
    ];

    // Los hashes nunca deben salir en un JSON
    protected $hidden = [
        'codigo_hash',
        'token_registro_hash',
    ];

    protected function casts(): array
    {
        return [
            'intentos' => 'integer',
            'expira_at' => 'datetime',
            'usado_at' => 'datetime',
            'ultimo_envio_at' => 'datetime',
            'token_registro_expira_at' => 'datetime',
        ];
    }
}