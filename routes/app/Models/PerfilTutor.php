<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilTutor extends Model
{
    protected $table = 'perfil_tutor';
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'estado', 'motivo_rechazo', 'bio',
        'lugar_presencial_default', 'enlace_virtual_default',
        'aprobado_por', 'fecha_aprobacion',
        'promedio_calificacion', 'total_calificaciones',
        'total_cumplidas', 'total_no_realizadas'
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'tutor_materias', 'tutor_id', 'materia_id');
    }

    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidad::class, 'tutor_id', 'user_id');
    }
}
