<?php

namespace App\Services;

use App\Models\Etiqueta;
use App\Models\ExistenciaPieza;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único punto que cambia las existencias de las piezas.
 *
 * Cada operación guarda en la misma transacción el movimiento, las existencias por hueco y el saldo total
 * de la pieza (piezas.stock), bloqueando las filas afectadas para que dos operaciones simultáneas no
 * descuenten las mismas unidades.
 *
 * Cada envío lleva una clave única (clave_envio): si llega dos veces (doble clic, reenvío del formulario),
 * la segunda no hace nada y devuelve lo que se registró la primera vez.
 *
 * Los huecos se identifican por la etiqueta del almacén interior. Sin etiqueta (null) es el stock
 * «Pendiente de ubicar»: puede ser origen de una venta, una baja o un traslado, pero nunca destino.
 */
class InventarioPiezas
{
    /**
     * Da de alta una pieza y registra su Entrada inicial en el hueco indicado.
     *
     * @param  array<string, mixed>  $datos  Campos de la pieza (nombre, precio, estado, stock_minimo, id_tipo_pieza, id_modelo_vehiculo).
     *
     * @throws ValidationException
     */
    public function darDeAlta(array $datos, int $cantidad, int $idEtiqueta, User $usuario, string $clave): Pieza
    {
        if ($repetido = $this->movimientoYaRegistrado($clave)) {
            return Pieza::query()->withoutGlobalScopes()->findOrFail($repetido->id_pieza);
        }

        $this->exigirCantidad($cantidad);

        try {
            return DB::transaction(function () use ($datos, $cantidad, $idEtiqueta, $usuario, $clave): Pieza {
                $pieza = new Pieza;
                $pieza->fill($datos);
                $pieza->id_usuario = $usuario->getKey();
                $pieza->stock = 0;
                $pieza->save();

                $this->aplicar($pieza, Movimiento::ENTRADA, $cantidad, null, $idEtiqueta, null, $usuario, $clave);

                return $pieza;
            });
        } catch (UniqueConstraintViolationException) {
            // Otro envío con la misma clave se ha registrado a la vez: se devuelve ese.
            return Pieza::query()->withoutGlobalScopes()->findOrFail($this->movimientoYaRegistrado($clave)->id_pieza);
        }
    }

    /**
     * Registra una acción de inventario sobre una pieza existente.
     *
     * @param  array{cantidad?: int|null, origen?: int|null, destino?: int|null, motivo?: string|null, retirar_ficha?: bool}  $datos
     *                                                                                                                                origen/destino: id de etiqueta, o null para «Pendiente de ubicar».
     * @return Collection<int, Movimiento> Movimientos registrados (una baja de la ficha completa registra uno por hueco).
     *
     * @throws ValidationException
     */
    public function registrar(Pieza $pieza, string $accion, array $datos, User $usuario, string $clave): Collection
    {
        if (! array_key_exists($accion, Movimiento::ACCIONES)) {
            throw ValidationException::withMessages(['accion' => 'La acción elegida no es válida.']);
        }

        if ($this->movimientoYaRegistrado($clave)) {
            return Movimiento::query()->withoutGlobalScopes()->where('clave_envio', 'like', $clave.'%')->orderBy('id_movimiento')->get();
        }

        try {
            return DB::transaction(function () use ($pieza, $accion, $datos, $usuario, $clave): Collection {
                $pieza = Pieza::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($pieza->getKey());

                if ($accion === Movimiento::BAJA && ($datos['retirar_ficha'] ?? false)) {
                    return $this->retirarFicha($pieza, (string) ($datos['motivo'] ?? ''), $usuario, $clave);
                }

                $cantidad = (int) ($datos['cantidad'] ?? 0);
                $this->exigirCantidad($cantidad);

                $movimiento = match ($accion) {
                    Movimiento::ENTRADA, Movimiento::COMPRA => $this->aplicar(
                        $pieza, $accion, $cantidad, null, $this->exigirHueco($datos['destino'] ?? null, 'destino'), null, $usuario, $clave,
                    ),
                    Movimiento::VENTA => $this->aplicar(
                        $pieza, $accion, $cantidad, $datos['origen'] ?? null, null, null, $usuario, $clave,
                    ),
                    Movimiento::BAJA => $this->aplicar(
                        $pieza, $accion, $cantidad, $datos['origen'] ?? null, null, $this->exigirMotivo($datos['motivo'] ?? null), $usuario, $clave,
                    ),
                    Movimiento::TRASLADO => $this->aplicar(
                        $pieza, $accion, $cantidad, $datos['origen'] ?? null, $this->exigirHueco($datos['destino'] ?? null, 'destino'), null, $usuario, $clave,
                    ),
                };

                return new Collection([$movimiento]);
            });
        } catch (UniqueConstraintViolationException) {
            return Movimiento::query()->withoutGlobalScopes()->where('clave_envio', 'like', $clave.'%')->orderBy('id_movimiento')->get();
        }
    }

    /**
     * Baja de la ficha completa: descuenta todas las unidades (un movimiento por hueco) y archiva la pieza
     * con su historial (borrado lógico).
     *
     * @return Collection<int, Movimiento>
     */
    private function retirarFicha(Pieza $pieza, string $motivo, User $usuario, string $clave): Collection
    {
        $motivo = $this->exigirMotivo($motivo);
        $movimientos = new Collection;

        $existencias = ExistenciaPieza::query()
            ->withoutGlobalScopes()
            ->where('id_pieza', $pieza->getKey())
            ->where('cantidad', '>', 0)
            ->orderByRaw('id_etiqueta is null desc')
            ->orderBy('id_existencia')
            ->lockForUpdate()
            ->get();

        foreach ($existencias->values() as $indice => $existencia) {
            $movimientos->push($this->aplicar(
                $pieza, Movimiento::BAJA, $existencia->cantidad, $existencia->id_etiqueta, null, $motivo, $usuario,
                $indice === 0 ? $clave : $clave.'-'.($indice + 1),
            ));
            $pieza->refresh();
        }

        // Sin unidades también queda constancia de la retirada en el historial.
        if ($movimientos->isEmpty()) {
            $movimientos->push($this->crearMovimiento($pieza, Movimiento::BAJA, 0, null, null, 0, 0, $motivo, $usuario, $clave));
        }

        $pieza->delete();

        return $movimientos;
    }

    /**
     * Aplica la acción sobre las existencias y el saldo de la pieza y deja el movimiento registrado.
     */
    private function aplicar(
        Pieza $pieza,
        string $accion,
        int $cantidad,
        ?int $origen,
        ?int $destino,
        ?string $motivo,
        User $usuario,
        string $clave,
    ): Movimiento {
        // Un hueco archivado puede ser origen (las unidades siguen ahí), pero nunca destino.
        $this->exigirHuecoDeLaSede($pieza, $origen, 'origen', permitirArchivado: true);
        $this->exigirHuecoDeLaSede($pieza, $destino, 'destino', permitirArchivado: false);

        if ($accion === Movimiento::TRASLADO && $origen === $destino) {
            throw ValidationException::withMessages(['destino' => 'El hueco de destino debe ser distinto del de origen.']);
        }

        $saldoAnterior = (int) $pieza->stock;

        if (in_array($accion, [...Movimiento::DESCUENTAN, Movimiento::TRASLADO], true)) {
            $this->descontar($pieza, $origen, $cantidad);
        }

        if (in_array($accion, [...Movimiento::SUMAN, Movimiento::TRASLADO], true)) {
            $this->sumar($pieza, $destino, $cantidad);
        }

        $pieza->stock = (int) ExistenciaPieza::query()->withoutGlobalScopes()->where('id_pieza', $pieza->getKey())->sum('cantidad');
        $pieza->save();

        return $this->crearMovimiento($pieza, $accion, $cantidad, $origen, $destino, $saldoAnterior, $pieza->stock, $motivo, $usuario, $clave);
    }

    private function descontar(Pieza $pieza, ?int $hueco, int $cantidad): void
    {
        $existencia = $this->existencia($pieza, $hueco);
        $disponibles = $existencia?->cantidad ?? 0;

        if ($cantidad > $disponibles) {
            throw ValidationException::withMessages([
                'cantidad' => $disponibles === 0
                    ? 'No hay unidades de esta pieza en el hueco de origen.'
                    : "Solo hay {$disponibles} ".($disponibles === 1 ? 'unidad' : 'unidades').' en el hueco de origen.',
            ]);
        }

        $existencia->cantidad = $disponibles - $cantidad;

        // Un hueco vacío deja de figurar en la ficha.
        $existencia->cantidad === 0 ? $existencia->delete() : $existencia->save();
    }

    private function sumar(Pieza $pieza, ?int $hueco, int $cantidad): void
    {
        $existencia = $this->existencia($pieza, $hueco) ?? new ExistenciaPieza([
            'id_pieza' => $pieza->getKey(),
            'id_etiqueta' => $hueco,
            'cantidad' => 0,
        ]);

        $existencia->cantidad += $cantidad;
        $existencia->save();
    }

    private function existencia(Pieza $pieza, ?int $hueco): ?ExistenciaPieza
    {
        return ExistenciaPieza::query()
            ->withoutGlobalScopes()
            ->where('id_pieza', $pieza->getKey())
            ->when($hueco === null, fn ($consulta) => $consulta->whereNull('id_etiqueta'), fn ($consulta) => $consulta->where('id_etiqueta', $hueco))
            ->lockForUpdate()
            ->first();
    }

    private function crearMovimiento(
        Pieza $pieza,
        string $accion,
        int $cantidad,
        ?int $origen,
        ?int $destino,
        int $saldoAnterior,
        int $saldoPosterior,
        ?string $motivo,
        User $usuario,
        string $clave,
    ): Movimiento {
        $movimiento = new Movimiento;
        $movimiento->tipo_movimiento = $accion;
        $movimiento->fecha = now()->toDateString();
        $movimiento->cantidad = $cantidad;
        $movimiento->id_pieza = $pieza->getKey();
        // La categoría de la pieza determina el desguace del movimiento.
        $movimiento->id_categoria = $pieza->tipoPieza()->withoutGlobalScopes()->value('id_categoria');
        $movimiento->id_usuario = $usuario->getKey();
        $movimiento->id_etiqueta_origen = $origen;
        $movimiento->id_etiqueta_destino = $destino;
        $movimiento->saldo_anterior = $saldoAnterior;
        $movimiento->saldo_posterior = $saldoPosterior;
        $movimiento->motivo = $motivo;
        $movimiento->clave_envio = $clave;
        $movimiento->save();

        return $movimiento;
    }

    /**
     * El hueco debe pertenecer al mismo desguace que la pieza. Se comprueba aquí además de en el
     * formulario, para que ninguna vía (web, API, consola) pueda cruzar sedes.
     */
    private function exigirHuecoDeLaSede(Pieza $pieza, ?int $hueco, string $campo, bool $permitirArchivado): void
    {
        if ($hueco === null) {
            return;
        }

        $idDesguace = $pieza->tipoPieza()->withoutGlobalScopes()
            ->join('categorias', 'categorias.categoria_id', '=', 'tipo_piezas.id_categoria')
            ->value('categorias.id_desguace');

        $valido = $idDesguace !== null && Etiqueta::query()
            ->withoutGlobalScopes()
            ->when(! $permitirArchivado, fn ($consulta) => $consulta->whereNull('deleted_at'))
            ->delDesguace((int) $idDesguace)
            ->whereKey($hueco)
            ->exists();

        if (! $valido) {
            throw ValidationException::withMessages([$campo => 'El hueco elegido no existe o no pertenece al desguace de la pieza.']);
        }
    }

    private function exigirHueco(mixed $hueco, string $campo): int
    {
        if (! is_numeric($hueco) || (int) $hueco <= 0) {
            throw ValidationException::withMessages([$campo => 'Elige el hueco de destino.']);
        }

        return (int) $hueco;
    }

    private function exigirCantidad(int $cantidad): void
    {
        if ($cantidad < 1) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser un número entero mayor que cero.']);
        }
    }

    private function exigirMotivo(?string $motivo): string
    {
        $motivo = trim((string) $motivo);

        if ($motivo === '') {
            throw ValidationException::withMessages(['motivo' => 'Indica el motivo de la baja.']);
        }

        return $motivo;
    }

    private function movimientoYaRegistrado(string $clave): ?Movimiento
    {
        return Movimiento::query()->withoutGlobalScopes()->where('clave_envio', $clave)->first();
    }
}
