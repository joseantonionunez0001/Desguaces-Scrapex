<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use App\Models\Etiqueta;
use App\Models\Movimiento;
use App\Rules\ExisteEnMiSede;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro de un movimiento sobre una pieza (la pieza viene de la ruta, ya limitada a la sede).
 *
 * Los campos dependen de la acción:
 *  - Entrada y Compra: cantidad y hueco de destino.
 *  - Venta: cantidad y hueco de origen.
 *  - Eliminar pieza: cantidad, hueco de origen y motivo; o retirar la ficha completa (con motivo).
 *  - Mover pieza: cantidad, hueco de origen y hueco de destino.
 *
 * Los campos que no corresponden a la acción elegida se ignoran.
 * El hueco de origen puede ser «pendiente» (stock pendiente de ubicar). Las existencias, la sede del
 * hueco y los traslados al mismo hueco se comprueban de nuevo en App\Services\InventarioPiezas.
 */
class MovimientoRequest extends FormRequest
{
    use MensajesDeValidacion;

    public function authorize(): bool
    {
        $usuario = $this->user();

        // Retirar la ficha completa archiva la pieza: exige además el permiso de eliminar piezas.
        if ($this->boolean('retirar_ficha') && $this->input('accion') === Movimiento::BAJA) {
            return $usuario?->can('piezas.eliminar') ?? false;
        }

        return $usuario?->can('movimientos.crear') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $accion = $this->input('accion');
        $retirarFicha = $accion === Movimiento::BAJA && $this->boolean('retirar_ficha');
        $usaOrigen = in_array($accion, [Movimiento::VENTA, Movimiento::BAJA, Movimiento::TRASLADO], true) && ! $retirarFicha;
        $usaDestino = in_array($accion, [Movimiento::ENTRADA, Movimiento::COMPRA, Movimiento::TRASLADO], true);

        return [
            'accion' => ['required', 'string', Rule::in(array_keys(Movimiento::ACCIONES))],
            'cantidad' => $retirarFicha ? ['exclude'] : ['required', 'integer', 'min:1', 'max:4294967295'],
            'origen' => $usaOrigen
                ? ['required', 'string', 'max:20', 'regex:/^(pendiente|\d+)$/']
                : ['exclude'],
            'destino' => $usaDestino
                ? ['required', new ExisteEnMiSede(Etiqueta::class), 'different:origen']
                : ['exclude'],
            'motivo' => $accion === Movimiento::BAJA ? ['required', 'string', 'max:500'] : ['exclude'],
            'retirar_ficha' => ['nullable', 'boolean'],
            'clave_envio' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * Datos listos para el servicio de inventario (origen «pendiente» se traduce a null).
     *
     * @return array{cantidad: int|null, origen: int|null, destino: int|null, motivo: string|null, retirar_ficha: bool}
     */
    public function datosDelMovimiento(): array
    {
        $datos = $this->validated();
        $origen = $datos['origen'] ?? null;

        return [
            'cantidad' => isset($datos['cantidad']) ? (int) $datos['cantidad'] : null,
            'origen' => $origen === null || $origen === 'pendiente' ? null : (int) $origen,
            'destino' => isset($datos['destino']) ? (int) $datos['destino'] : null,
            'motivo' => $datos['motivo'] ?? null,
            'retirar_ficha' => (bool) ($datos['retirar_ficha'] ?? false),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'accion' => 'acción',
            'cantidad' => 'cantidad',
            'origen' => 'hueco de origen',
            'destino' => 'hueco de destino',
            'motivo' => 'motivo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->mensajesEnEspanol(['accion', 'cantidad', 'origen', 'destino', 'motivo']),
            'accion.in' => 'Elige una de las cinco acciones.',
            'cantidad.max' => 'La cantidad es demasiado alta.',
            'origen.regex' => 'El hueco de origen no es válido.',
            'destino.different' => 'El hueco de destino debe ser distinto del de origen.',
            'clave_envio.required' => 'El formulario ha caducado. Vuelve a abrirlo e inténtalo de nuevo.',
            'clave_envio.uuid' => 'El formulario ha caducado. Vuelve a abrirlo e inténtalo de nuevo.',
        ];
    }
}
