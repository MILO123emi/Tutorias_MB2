<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aviso extends Model
{
    protected $table = 'avisos';

    protected $fillable = [
        'admin_id',
        'destinatarios',
        'titulo',
        'mensaje',
        'enviar_correo',
    ];

    protected function casts(): array
    {
        return [
            'enviar_correo' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}