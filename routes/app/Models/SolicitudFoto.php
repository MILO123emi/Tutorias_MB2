<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudFoto extends Model
{
    protected $table = 'solicitudes_foto';
    protected $fillable = ['user_id', 'foto_nueva_url', 'estado', 'motivo_rechazo', 'revisado_por'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
