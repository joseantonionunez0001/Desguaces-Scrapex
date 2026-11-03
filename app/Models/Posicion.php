<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posicion extends Model
{
    use HasFactory;

    protected $table = 'posiciones';

    protected $primaryKey = 'id';

    protected $fillable = [
        'fila_id',
        'numero_posicion',
        'capacidad_maxima'
    ];

    // Una posición pertenece a una fila.
    public function fila()
    {
        return $this->belongsTo(Fila::class);
    }

    // Una posición tiene varios niveles.
    public function niveles()
    {
        return $this->hasMany(Nivel::class);
    }
}
