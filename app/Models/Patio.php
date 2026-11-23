<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patio extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

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

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $consulta->where($consulta->getModel()->qualifyColumn('id_desguace'), $idDesguace);
    }
}
