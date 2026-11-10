<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disponibilidad extends Model
{
    protected $table = 'disponibilidad';
    protected $fillable = ['tutor_id', 'dia_semana', 'hora_inicio', 'modalidad', 'lugar_o_enlace'];

    public function tutor()
    {
        return $this->belongsTo(PerfilTutor::class, 'tutor_id', 'user_id');
    }
}
