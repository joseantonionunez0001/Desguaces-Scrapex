<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasillo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pasillos';
    protected $primaryKey = 'id_pasillo';

    protected $fillable = [
        'id_modulo',
        'numero_pasillo',
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id_modulo');
    }

    public function estanterias(): HasMany
    {
        return $this->hasMany(Estanteria::class, 'id_pasillo');
    }
}
