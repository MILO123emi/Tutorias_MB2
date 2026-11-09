<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';

    protected $fillable = [
        'tutoria_id',
        'estudiante_id',
        'tutor_id',
        'puntuacion',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'puntuacion' => 'integer',
        ];
    }

    public function tutoria(): BelongsTo
    {
        return $this->belongsTo(Tutoria::class, 'tutoria_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}