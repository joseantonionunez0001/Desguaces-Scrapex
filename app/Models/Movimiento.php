<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Movimiento de inventario.
 *
 * Los movimientos nuevos se registran sobre una pieza con una de las cinco acciones (ACCIONES) y guardan
 * el usuario, los huecos de origen y destino, el saldo total de la pieza antes y después, y el motivo de
 * las bajas. Los movimientos anteriores a esta versión (sin pieza, con tipo de texto libre) se conservan
 * como históricos de solo lectura.
 *
 * Solo los crea App\Services\InventarioPiezas: el historial no se edita ni se borra.
 */
class Movimiento extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    public const ENTRADA = 'entrada';

    public const COMPRA = 'compra';

    public const VENTA = 'venta';

    public const BAJA = 'baja';

    public const TRASLADO = 'traslado';

    /**
     * Acciones de inventario, con su nombre en pantalla.
     */
    public const ACCIONES = [
        self::ENTRADA => 'Entrada',
        self::COMPRA => 'Compra',
        self::VENTA => 'Venta',
        self::BAJA => 'Eliminar pieza',
        self::TRASLADO => 'Mover pieza',
    ];

    /**
     * Acciones que suman unidades en el hueco de destino.
     */
    public const SUMAN = [self::ENTRADA, self::COMPRA];

    /**
     * Acciones que descuentan unidades del hueco de origen.
     */
    public const DESCUENTAN = [self::VENTA, self::BAJA];

    protected $table = 'movimientos';

    protected $primaryKey = 'id_movimiento';

    protected $fillable = [
        'tipo_movimiento',
        'fecha',
        'cantidad',
        'id_categoria',
        'id_pieza',
        'id_usuario',
        'id_etiqueta_origen',
        'id_etiqueta_destino',
        'saldo_anterior',
        'saldo_posterior',
        'motivo',
        'clave_envio',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad' => 'integer',
            'saldo_anterior' => 'integer',
            'saldo_posterior' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'categoria_id');
    }

    public function pieza(): BelongsTo
    {
        return $this->belongsTo(Pieza::class, 'id_pieza', 'id_pieza');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(Etiqueta::class, 'id_etiqueta_origen', 'id_etiqueta');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Etiqueta::class, 'id_etiqueta_destino', 'id_etiqueta');
    }

    /**
     * Movimiento anterior a esta versión: sin pieza y con el tipo como texto libre.
     */
    public function esHistorico(): bool
    {
        return $this->id_pieza === null || ! array_key_exists($this->tipo_movimiento, self::ACCIONES);
    }

    public function nombreAccion(): string
    {
        return self::ACCIONES[$this->tipo_movimiento] ?? (string) $this->tipo_movimiento;
    }

    /**
     * Hueco de origen en texto, o null si la acción no tiene origen.
     * Sin etiqueta, el origen era el stock «Pendiente de ubicar».
     */
    public function descripcionOrigen(): ?string
    {
        if ($this->esHistorico() || ! in_array($this->tipo_movimiento, [...self::DESCUENTAN, self::TRASLADO], true) || $this->cantidad === 0) {
            return null;
        }

        return $this->origen?->rutaCompleta() ?? 'Pendiente de ubicar';
    }

    /**
     * Hueco de destino en texto, o null si la acción no tiene destino.
     */
    public function descripcionDestino(): ?string
    {
        if ($this->esHistorico() || ! in_array($this->tipo_movimiento, [...self::SUMAN, self::TRASLADO], true)) {
            return null;
        }

        return $this->destino?->rutaCompleta();
    }

    /**
     * Alcance por desguace: usa las relaciones reales del modelo. Los movimientos nuevos guardan la
     * categoría de su pieza, así que la regla vale para los nuevos y para los históricos.
     */
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'categoria', $idDesguace);
    }
}
