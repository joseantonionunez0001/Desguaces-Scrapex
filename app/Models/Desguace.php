<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Desguace extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'desguaces';
    protected $primaryKey = 'id_desguace';

    protected $fillable = [
        'nombre',
        'direccion',
        'numero_telefono',
        'ubicación',
    ];

    public function modulos(): HasMany
    {
        return $this->hasMany(Modulo::class, 'id_desguace');
    }

    public function patios(): HasMany
    {
        return $this->hasMany(Patio::class, 'id_desguace');
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'id_desguace');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'id_desguace');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_desguace');
    }
}
