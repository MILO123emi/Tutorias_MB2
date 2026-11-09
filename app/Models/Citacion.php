<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Citacion extends Model
{
    protected $table = 'citaciones';

    // 'enterado_at' lo marca el citado desde su propio endpoint.
    protected $fillable = [
        'admin_id',
        'user_id',
        'bitacora_id',
        'tutoria_id',
        'mensaje',
        'fecha_cita',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cita' => 'datetime',
            'enterado_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // La persona citada
    public function citado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bitacora(): BelongsTo
    {
        return $this->belongsTo(Bitacora::class, 'bitacora_id');
    }

    public function tutoria(): BelongsTo
    {
        return $this->belongsTo(Tutoria::class, 'tutoria_id');
    }
}