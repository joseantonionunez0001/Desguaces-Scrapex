<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modulo extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

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

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $consulta->where($consulta->getModel()->qualifyColumn('id_desguace'), $idDesguace);
    }
}
