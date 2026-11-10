<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutoriaNoRealizada extends Model
{
    protected $table = 'tutorias_no_realizadas';

    protected $fillable = [
        'tutoria_id', 'tipo', 'reportado_por', 'categoria_motivo',
        'motivo_para_admin', 'motivo_para_contraparte', 'revisado_at', 'revisado_por'
    ];

    protected $casts = [
        'revisado_at' => 'datetime',
    ];

    public function tutoria()
    {
        return $this->belongsTo(Tutoria::class);
    }
}
