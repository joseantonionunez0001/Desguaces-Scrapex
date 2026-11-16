<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patios';
    protected $primaryKey = 'id_patio';

    protected $fillable = [
        'id_desguace',
        'codigo',
        'nombre',
    ];

    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class, 'id_desguace', 'id_desguace');
    }

    public function sectores(): HasMany
    {
        return $this->hasMany(Sector::class, 'id_patio');
    }
}
