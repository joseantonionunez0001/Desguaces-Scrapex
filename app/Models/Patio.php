<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patio extends Model
{
    use HasFactory;

    protected $table = 'patios';
    protected $primaryKey = 'id';
    protected $fillable = [
        'desguace_id',
        'codigo',
        'nombre'
    ];

    // Un patio pertenece a un desguace (N:1)
    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class);
    }

    // Un patio tiene muchos sectores (1:N)
    public function sectores(): HasMany
    {
        return $this->hasMany(Sector::class, 'patio_id');
    }
}
