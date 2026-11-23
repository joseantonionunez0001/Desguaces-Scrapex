<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Posicion extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'posiciones';

    protected $primaryKey = 'id_posicion';

    protected $fillable = [
        'id_fila',
        'numero_posicion',
        'capacidad_maxima',
    ];

    public function fila(): BelongsTo
    {
        return $this->belongsTo(Fila::class, 'id_fila', 'id_fila');
    }

    public function niveles(): HasMany
    {
        return $this->hasMany(Nivel::class, 'id_posicion');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'fila', $idDesguace);
    }
}
