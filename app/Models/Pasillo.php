<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasillo extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'pasillos';

    protected $primaryKey = 'id_pasillo';

    protected $fillable = [
        'id_modulo',
        'numero_pasillo',
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id_modulo');
    }

    public function estanterias(): HasMany
    {
        return $this->hasMany(Estanteria::class, 'id_pasillo');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'modulo', $idDesguace);
    }
}
