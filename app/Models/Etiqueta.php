<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etiqueta extends Model
{
    use HasFactory;

    protected $table = 'etiquetas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'estanteria_id',
        'numero_etiqueta'
    ];

    // Etiqueta (N) -> Estanteria (1)
    public function estanteria()
    {
        return $this->belongsTo(Estanteria::class, 'estanteria_id');
    }
}
