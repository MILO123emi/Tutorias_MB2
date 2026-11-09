<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutoriaNoRealizada extends Model
{
    protected $table = 'tutorias_no_realizadas';

    // 'revisado_at' y 'revisado_por' los pone el código cuando la admin marca "revisado".
    protected $fillable = [
        'tutoria_id',
        'tipo',
        'reportado_por',
        'categoria_motivo',
        'motivo_para_admin',
        'motivo_para_contraparte',
    ];

    protected function casts(): array
    {
        return [
            'revisado_at' => 'datetime',
        ];
    }

    public function scopeSinRevisar(Builder $query): Builder
    {
        return $query->whereNull('revisado_at');
    }

    public function tutoria(): BelongsTo
    {
        return $this->belongsTo(Tutoria::class, 'tutoria_id');
    }

    public function reportador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}