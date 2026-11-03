<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPieza extends Model
{
    use HasFactory;

    protected $table = 'tipo_piezas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'categoria',
        'fabricante'
    ];

    // Sin relaciones por ahora. Pieza (N:1) Tipo_pieza aún no tiene clave foránea en el esquema.
}
