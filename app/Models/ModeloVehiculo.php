<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de vehículo del catálogo común. Siempre pertenece a una marca.
 */
class ModeloVehiculo extends Model
{
    use HasFactory;

    protected $table = 'modelos_vehiculo';

    protected $primaryKey = 'id_modelo';

    protected $fillable = ['id_marca', 'nombre'];

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca', 'id_marca');
    }
}
