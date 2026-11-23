<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehiculos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'marca',
        'modelo',
        'anio'
    ];

    public function piezas()
    {
        return $this->belongsToMany(
            Pieza::class,
            'pieza_vehiculo',
            'vehiculo_id',
            'pieza_id'
        )
        ->using(PiezaVehiculo::class)
        ->withPivot('observaciones')
        ->withTimestamps();
    }
}