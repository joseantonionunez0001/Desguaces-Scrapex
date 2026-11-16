<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etiqueta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'etiquetas';
    protected $primaryKey = 'id_etiqueta';

    protected $fillable = [
        'id_estanteria',
        'numero_etiqueta',
    ];

    public function estanteria(): BelongsTo
    {
        return $this->belongsTo(Estanteria::class, 'id_estanteria', 'id_estanteria');
    }
}
