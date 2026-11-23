<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etiqueta extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'etiquetas';

    protected $primaryKey = 'id_etiqueta';

    protected $fillable = [
        'id_estanteria',
        'numero_etiqueta',
    ];

    public function estanteria(): BelongsTo
    {
        return $this->belongsTo(Estanteria::class, 'id_estanteria', 'id_estanteria');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'estanteria', $idDesguace);
    }
}
