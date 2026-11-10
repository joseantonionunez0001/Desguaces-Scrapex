<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fila extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'filas';
    protected $primaryKey = 'id_fila';

    protected $fillable = [
        'id_sector',
        'numero_fila',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'id_sector', 'id_sector');
    }

    public function posiciones(): HasMany
    {
        return $this->hasMany(Posicion::class, 'id_fila');
    }
}
