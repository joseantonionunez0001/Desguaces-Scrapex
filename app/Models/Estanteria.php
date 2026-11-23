<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estanteria extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'estanterias';

    protected $primaryKey = 'id_estanteria';

    protected $fillable = [
        'id_pasillo',
        'numero_estanteria',
    ];

    public function pasillo(): BelongsTo
    {
        return $this->belongsTo(Pasillo::class, 'id_pasillo', 'id_pasillo');
    }

    public function etiquetas(): HasMany
    {
        return $this->hasMany(Etiqueta::class, 'id_estanteria');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'pasillo', $idDesguace);
    }
}
