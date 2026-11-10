<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carreras';

    protected $fillable = ['nombre', 'facultad'];

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}