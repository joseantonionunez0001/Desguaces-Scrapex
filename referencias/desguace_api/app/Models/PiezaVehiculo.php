<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PiezaVehiculo extends Pivot
{
    protected $table = 'pieza_vehiculo';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'pieza_id',
        'vehiculo_id',
        'observaciones'
    ];
}
