<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    use HasFactory;

    protected $table = 'niveles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'posicion_id',
        'numero_nivel'
    ];

    // Un nivel pertenece a una posición.
    public function posicion()
    {
        return $this->belongsTo(Posicion::class);
    }
}
