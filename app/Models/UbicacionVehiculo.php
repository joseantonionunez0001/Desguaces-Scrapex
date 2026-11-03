<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UbicacionVehiculo extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_vehiculo';
    protected $primaryKey = 'id';

    protected $fillable = [
        'vehiculo_id',
        'nivel_id',
        'fecha_inicio',
        'fecha_fin'
    ];

    // Relaciones con Vehiculo y Nivel pendientes: esos modelos aún no existen.
}
