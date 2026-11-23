<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unidades de una pieza en un hueco del almacén interior.
 * Sin etiqueta (id_etiqueta nulo) es el stock «Pendiente de ubicar».
 *
 * Solo la modifica App\Services\InventarioPiezas, dentro de la transacción del movimiento.
 */
class ExistenciaPieza extends Model
{
    use RestringidoPorDesguace;

    protected $table = 'existencias_pieza';

    protected $primaryKey = 'id_existencia';

    protected $fillable = ['id_pieza', 'id_etiqueta', 'cantidad'];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
        ];
    }

    public function pieza(): BelongsTo
    {
        return $this->belongsTo(Pieza::class, 'id_pieza', 'id_pieza');
    }

    public function etiqueta(): BelongsTo
    {
        return $this->belongsTo(Etiqueta::class, 'id_etiqueta', 'id_etiqueta');
    }

    public function estaPendienteDeUbicar(): bool
    {
        return $this->id_etiqueta === null;
    }

    /**
     * Alcance por desguace: el de su pieza.
     */
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'pieza', $idDesguace);
    }
}
