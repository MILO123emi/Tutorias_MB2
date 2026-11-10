<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutoria extends Model
{
    protected $table = 'tutorias';

    protected $fillable = [
        'estudiante_id', 'tutor_id', 'materia_id', 'tema', 'descripcion',
        'fecha', 'hora_inicio', 'modalidad', 'lugar_o_enlace', 'estado', 'asistencia_confirmada_at'
    ];

    protected $casts = [
        'asistencia_confirmada_at' => 'datetime',
    ];

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function evaluacion()
    {
        return $this->hasOne(Evaluacion::class);
    }

    public function noRealizada()
    {
        return $this->hasOne(TutoriaNoRealizada::class);
    }
}
