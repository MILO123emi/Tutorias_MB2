<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfilTutor extends Model
{
    protected $table = 'perfil_tutor';

    // Los campos de caché (promedio, totales) y el estado de aprobación
    // los cambia el código del servidor, no se llenan desde una petición.
    protected $fillable = [
        'user_id',
        'bio',
        'lugar_presencial_default',
        'enlace_virtual_default',
    ];

    protected function casts(): array
    {
        return [
            'fecha_aprobacion' => 'datetime',
            'promedio_calificacion' => 'decimal:2',
            'total_calificaciones' => 'integer',
            'total_cumplidas' => 'integer',
            'total_no_realizadas' => 'integer',
        ];
    }

    public function scopeAprobados(Builder $query): Builder
    {
        return $query->where('estado', 'APROBADO');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}