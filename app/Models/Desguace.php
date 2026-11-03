<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desguace extends Model
{
    use HasFactory;

    protected $table = 'desguaces';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'direccion',
        'numero_telefono',
        'ubicacion'
    ];

    // Sin relaciones por ahora. Patio (1:N) se añadirá cuando exista su modelo.
}
