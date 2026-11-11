<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tutoria extends Model
{
    protected $table = 'tutorias';

    // 'estado' y 'asistencia_confirmada_at' los cambia el TutoriaService.
    // 'slot_activo' es una columna generada por MySQL: nunca se escribe.
    protected $fillable = [
        'estudiante_id',
        'tutor_id',
        'materia_id',
        'tema',
        'descripcion',
        'fecha',
        'hora_inicio',
        'modalidad',
        'lugar_o_enlace',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'hora_inicio' => 'integer',
            'asistencia_confirmada_at' => 'datetime',
        ];
    }

    // Tutorías que ocupan el bloque (las mismas que cubre la restricción anti doble reserva)
    public function scopeOcupanBloque(Builder $query): Builder
    {
        return $query->whereIn('estado', ['CONFIRMADA', 'COMPLETADA']);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function noRealizada(): HasOne
    {
        return $this->hasOne(TutoriaNoRealizada::class, 'tutoria_id');
    }

    public function evaluacion(): HasOne
    {
        return $this->hasOne(Evaluacion::class, 'tutoria_id');
    }
}
