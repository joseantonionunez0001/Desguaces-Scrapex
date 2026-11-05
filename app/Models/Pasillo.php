<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasillo extends Model
{
    use HasFactory;

    protected $table = 'pasillos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'numero_pasillo'
    ];

    // Sin relaciones por ahora. El EER relaciona Modulo con Pasillo, pero no
    // muestra clave foránea. Se definirá cuando exista el modelo Modulo.
}
