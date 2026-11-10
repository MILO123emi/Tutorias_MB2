<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';

    protected $fillable = ['nombre', 'carrera_id'];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    // Tutores que dominan esta materia
    public function tutores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tutor_materias', 'materia_id', 'tutor_id');
    }
}