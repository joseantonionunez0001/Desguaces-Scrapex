<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fila extends Model
{
    use HasFactory;

    protected $table = 'filas';

    protected $primaryKey = 'id';

    protected $fillable = [
        'sector_id',
        'numero_fila'
    ];

    // Sector (1:N) se añadirá cuando exista el modelo Sector.

    // Una fila tiene varias posiciones.
    public function posiciones()
    {
        return $this->hasMany(Posicion::class);
    }
}
