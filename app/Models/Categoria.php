<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'categorias';

    protected $primaryKey = 'categoria_id';

    protected $fillable = [
        'nombre',
        'id_desguace',
        'id_modulo',
        'id_vehiculo',
    ];

    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class, 'id_desguace', 'id_desguace');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id_modulo');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    public function tiposPieza(): HasMany
    {
        return $this->hasMany(TipoPieza::class, 'id_categoria', 'categoria_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'id_categoria', 'categoria_id');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $consulta->where($consulta->getModel()->qualifyColumn('id_desguace'), $idDesguace);
    }
}
