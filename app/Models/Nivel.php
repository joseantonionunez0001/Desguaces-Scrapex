<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nivel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'niveles';
    protected $primaryKey = 'id_nivel';

    protected $fillable = [
        'id_posicion',
        'numero_nivel',
    ];

    public function posicion(): BelongsTo
    {
        return $this->belongsTo(Posicion::class, 'id_posicion', 'id_posicion');
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(UbicacionVehiculo::class, 'id_nivel');
    }
}
