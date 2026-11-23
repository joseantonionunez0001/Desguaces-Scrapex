<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fila extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

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

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'sector', $idDesguace);
    }
}
