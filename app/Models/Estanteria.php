<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estanteria extends Model
{
    use HasFactory;

    protected $table = 'estanterias';
    protected $primaryKey = 'id';

    protected $fillable = [
        'pasillo_id',
        'numero_estanteria'
    ];

    // Estanteria (1) -> Etiqueta (N)
    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'estanteria_id');
    }

    // Pasillo (1) -> Estanteria (N): pendiente hasta que exista el modelo Pasillo.
}
