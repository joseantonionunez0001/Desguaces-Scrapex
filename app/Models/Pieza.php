<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Database\Factories\PiezaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pieza del inventario.
 *
 * "stock" es el total de unidades y "existencias" su reparto por hueco. Ambos solo cambian a través de
 * los movimientos (App\Services\InventarioPiezas), por eso "stock" no es asignable en masa.
 * El estado físico ("estado") es un dato independiente de la disponibilidad, que se calcula con el stock.
 */
class Pieza extends Model
{
    /** @use HasFactory<PiezaFactory> */
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    public const CON_STOCK = 'con_stock';

    public const AGOTADA = 'agotada';

    public const STOCK_BAJO = 'stock_bajo';

    public const DISPONIBILIDADES = [
        self::CON_STOCK => 'Con stock',
        self::STOCK_BAJO => 'Stock bajo',
        self::AGOTADA => 'Agotada',
    ];

    /**
     * Estados físicos que se ofrecen al registrar una pieza.
     */
    public const ESTADOS_FISICOS = [
        'Buen estado',
        'Pendiente de revisar',
        'Dañada',
    ];

    protected $table = 'piezas';

    protected $primaryKey = 'id_pieza';

    protected $fillable = [
        'nombre',
        'precio',
        'estado',
        'stock_minimo',
        'id_usuario',
        'id_tipo_pieza',
        'id_modelo_vehiculo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
            'stock_minimo' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function tipoPieza(): BelongsTo
    {
        return $this->belongsTo(TipoPieza::class, 'id_tipo_pieza', 'id_pieza');
    }

    public function modeloVehiculo(): BelongsTo
    {
        return $this->belongsTo(ModeloVehiculo::class, 'id_modelo_vehiculo', 'id_modelo');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(ExistenciaPieza::class, 'id_pieza', 'id_pieza');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'id_pieza', 'id_pieza');
    }

    /**
     * Con stock: hay unidades y el saldo supera el mínimo. Stock bajo: hay unidades, pero el saldo
     * es igual o inferior al mínimo. Agotada: stock cero.
     */
    public function disponibilidad(): string
    {
        if ($this->stock <= 0) {
            return self::AGOTADA;
        }

        return $this->stock <= $this->stock_minimo ? self::STOCK_BAJO : self::CON_STOCK;
    }

    /**
     * Filtro por disponibilidad con las mismas reglas que disponibilidad().
     */
    public function scopeConDisponibilidad(Builder $consulta, string $disponibilidad): void
    {
        match ($disponibilidad) {
            self::AGOTADA => $consulta->where('stock', '<=', 0),
            self::STOCK_BAJO => $consulta->where('stock', '>', 0)->whereColumn('stock', '<=', 'stock_minimo'),
            self::CON_STOCK => $consulta->where('stock', '>', 0)->whereColumn('stock', '>', 'stock_minimo'),
            default => null,
        };
    }

    /**
     * Alcance por desguace: usa las relaciones reales del modelo.
     */
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'tipoPieza', $idDesguace);
    }
}
