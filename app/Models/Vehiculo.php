<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehiculos';
    protected $primaryKey = 'id_vehiculo';

    protected $fillable = [
        'id_desguace',
        'matricula',
        'marca',
        'modelo',
        'fecha_llegada',
        'numero_bastidor',
        'estado_tratamiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_llegada' => 'date',
        ];
    }

    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class, 'id_desguace', 'id_desguace');
    }

    /**
     * Historial de ubicaciones del vehículo, de la más reciente a la más antigua.
     */
    public function ubicaciones(): HasMany
    {
        return $this->hasMany(UbicacionVehiculo::class, 'id_vehiculo', 'id_vehiculo')
            ->orderByDesc('fecha_inicio');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'id_vehiculo', 'id_vehiculo');
    }
}
