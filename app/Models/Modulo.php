<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modulo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'modulos';
    protected $primaryKey = 'id_modulo';

    protected $fillable = [
        'id_desguace',
        'nombre_modulo',
    ];

    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class, 'id_desguace', 'id_desguace');
    }

    public function pasillos(): HasMany
    {
        return $this->hasMany(Pasillo::class, 'id_modulo');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'id_modulo');
    }
}
