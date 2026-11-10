<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $fillable = ['nombre', 'facultad'];

    public function materias()
    {
        return $this->hasMany(Materia::class);
    }
}
