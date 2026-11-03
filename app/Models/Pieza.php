<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pieza extends Model
{
    use HasFactory;

    protected $table = 'piezas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'precio',
        'estado',
        'stock',
        'stock_minimo'
    ];

    // Sin relaciones por ahora. Pieza (N:1) Tipo_pieza aún no tiene clave foránea en el esquema.
}
