<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $table = 'movimientos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'tipo_movimiento',
        'fecha',
        'cantidad'
    ];

    // Sin relaciones por ahora. Movimiento (N:1) Categoria aún no tiene clave foránea en el esquema.
}
