<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudFoto extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_foto';

    // 'estado', 'motivo_rechazo' y 'revisado_por' los cambia la admin desde el servidor.
    protected $fillable = [
        'user_id',
        'foto_nueva_url',
    ];

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'PENDIENTE');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}