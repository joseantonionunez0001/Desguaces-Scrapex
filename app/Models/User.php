<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Acceso;
use Database\Factories\UserFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'id_desguace'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

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
        ];
    }

    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class, 'id_desguace', 'id_desguace');
    }

    public function piezas(): HasMany
    {
        return $this->hasMany(Pieza::class, 'id_usuario', 'id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroSistema::class, 'id_usuario', 'id');
    }

    public function esAdministradorPlataforma(): bool
    {
        return $this->hasRole(RoleSeeder::PLATAFORMA);
    }

    public function esAdministradorDesguace(): bool
    {
        return $this->hasRole(RoleSeeder::ADMIN_DESGUACE);
    }

    // Gerente, operario o administrador de desguace: roles que dependen de una sede.
    public function tieneRolDeDesguace(): bool
    {
        return $this->hasAnyRole(RoleSeeder::ROLES_DESGUACE);
    }

    // Id de la sede autorizada, o null si no puede acceder a ninguna.
    public function desguaceAutorizadoId(): ?int
    {
        return Acceso::desguaceId($this);
    }

    // Cuentas de una sede. No es un scope global: el login debe poder encontrar cualquier cuenta.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $consulta->where($consulta->getModel()->qualifyColumn('id_desguace'), $idDesguace);
    }
}
