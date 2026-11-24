<?php

namespace App\Models;

use Database\Factories\EvaluacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluacion extends Model
{
    /** @use HasFactory<EvaluacionFactory> */
    use HasFactory;

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

    /** @return BelongsTo<Tutoria, $this> */
    public function tutoria(): BelongsTo
    {
        return $this->belongsTo(Tutoria::class, 'tutoria_id');
    }

    /** @return BelongsTo<User, $this> */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    /** @return BelongsTo<User, $this> */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
