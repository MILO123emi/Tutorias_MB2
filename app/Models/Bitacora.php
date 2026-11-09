<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    protected $fillable = [
        'actor_id',
        'tipo_evento',
        'entidad',
        'entidad_id',
        'detalle',
        'requiere_atencion',
    ];

    protected function casts(): array
    {
        return [
            'requiere_atencion' => 'boolean',
            'revisado_at' => 'datetime',
        ];
    }

    // Vista por defecto de la admin: eventos (!) sin revisar
    public function scopePendientesDeAtencion(Builder $query): Builder
    {
        return $query->where('requiere_atencion', true)->whereNull('revisado_at');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}