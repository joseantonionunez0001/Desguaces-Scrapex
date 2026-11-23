<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sector extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'sectores';

    protected $primaryKey = 'id_sector';

    protected $fillable = [
        'id_patio',
        'codigo',
        'tipo_zona',
    ];

    public function patio(): BelongsTo
    {
        return $this->belongsTo(Patio::class, 'id_patio', 'id_patio');
    }

    public function filas(): HasMany
    {
        return $this->hasMany(Fila::class, 'id_sector');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'patio', $idDesguace);
    }
}
