<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'matricula',
        'marca',
        'modelo',
        'fecha_llegada',
        'numero_bastidor',
        'estado_tratamiento'
    ];

    // Sin relaciones por ahora. Desguace (1:N) Vehiculo aún no tiene clave foránea en el esquema.
}
