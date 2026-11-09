<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'carrera_id', 'telefono', 'foto_url'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    // 'rol' y 'activo' quedan fuera de Fillable a propósito (anti escalada de privilegios).

    public function esAdmin(): bool
    {
        return $this->rol === 'ADMIN';
    }

    public function esTutorAprobado(): bool
    {
        return $this->perfilTutor?->estado === 'APROBADO';
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function perfilTutor(): HasOne
    {
        return $this->hasOne(PerfilTutor::class);
    }

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'tutor_materias', 'tutor_id', 'materia_id');
    }

    public function disponibilidad(): HasMany
    {
        return $this->hasMany(Disponibilidad::class, 'tutor_id');
    }

    public function tutoriasComoEstudiante(): HasMany
    {
        return $this->hasMany(Tutoria::class, 'estudiante_id');
    }

    public function tutoriasComoTutor(): HasMany
    {
        return $this->hasMany(Tutoria::class, 'tutor_id');
    }
}
