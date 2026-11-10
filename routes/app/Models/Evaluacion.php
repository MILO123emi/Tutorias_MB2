<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';
    protected $fillable = ['tutoria_id', 'estudiante_id', 'tutor_id', 'puntuacion', 'comentario'];

    public function tutoria()
    {
        return $this->belongsTo(Tutoria::class);
    }
}
