<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoPieza extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_piezas';
    protected $primaryKey = 'id_pieza';

    protected $fillable = [
        'nombre',
        'fabricante',
        'categoria',
        'id_categoria',
    ];

    /**
     * Categoría a la que pertenece tipo de pieza.
     * El nombre no es "categoria()" porque esa palabra es el nombre de una columna de texto.
     */
    public function categoriaRelacionada(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'categoria_id');
    }

    public function piezas(): HasMany
    {
        return $this->hasMany(Pieza::class, 'id_tipo_pieza', 'id_pieza');
    }
}
