<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disponibilidad extends Model
{
    protected $table = 'disponibilidad';

    protected $fillable = [
        'tutor_id',
        'dia_semana',
        'hora_inicio',
        'modalidad',
        'lugar_o_enlace',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'hora_inicio' => 'integer',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}