<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pieza extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'piezas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'referencia',
        'precio',
        'stock'
    ];

    public function vehiculos()
    {
        return $this->belongsToMany(
            Vehiculo::class,
            'pieza_vehiculo',
            'pieza_id',
            'vehiculo_id'
        )
        ->using(PiezaVehiculo::class)
        ->withPivot('observaciones')
        ->withTimestamps();
    }
}
