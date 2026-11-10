<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    protected $table = 'avisos';
    protected $fillable = ['admin_id', 'destinatarios', 'titulo', 'mensaje', 'enviar_correo'];
}
