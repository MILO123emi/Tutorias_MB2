<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'rol', 'carrera_id', 'telefono', 'foto_url', 'activo', 'email_verified_at'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class);
    }

    public function perfilTutor()
    {
        return $this->hasOne(PerfilTutor::class, 'user_id', 'id');
    }

    public function tutoriasEstudiante()
    {
        return $this->hasMany(Tutoria::class, 'estudiante_id');
    }

    public function tutoriasTutor()
    {
        return $this->hasMany(Tutoria::class, 'tutor_id');
    }
}
