<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estanteria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'estanterias';
    protected $primaryKey = 'id_estanteria';

    protected $fillable = [
        'id_pasillo',
        'numero_estanteria',
    ];

    public function pasillo(): BelongsTo
    {
        return $this->belongsTo(Pasillo::class, 'id_pasillo', 'id_pasillo');
    }

    public function etiquetas(): HasMany
    {
        return $this->hasMany(Etiqueta::class, 'id_estanteria');
    }
}
