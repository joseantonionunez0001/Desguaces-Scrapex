<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\MovimientoRequest;
use App\Models\Etiqueta;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Services\InventarioPiezas;
use App\Support\OpcionesInventario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Movimientos de inventario: Entrada, Compra, Venta, Eliminar pieza y Mover pieza.
 *
 * Cada movimiento se registra sobre una pieza concreta con InventarioPiezas, que actualiza en la misma
 * transacción las existencias por hueco y el saldo. El historial no se edita ni se borra.
 */
class MovimientoController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = $this->textoDeConsulta($request, 'q');
        $accion = $this->textoDeConsulta($request, 'accion');
        $accion = array_key_exists($accion, Movimiento::ACCIONES) ? $accion : '';
        $desde = $this->fechaDeConsulta($request, 'desde');
        $hasta = $this->fechaDeConsulta($request, 'hasta');

        $movimientos = Movimiento::query()
            ->with([
                'pieza' => fn ($consulta) => $consulta->withTrashed(),
                'usuario' => fn ($consulta) => $consulta->withTrashed(),
                'categoria' => fn ($consulta) => $consulta->withTrashed(),
                'origen' => fn ($consulta) => $consulta->withTrashed(),
                'destino' => fn ($consulta) => $consulta->withTrashed(),
                ...Etiqueta::relacionesDeRuta('origen.'),
                ...Etiqueta::relacionesDeRuta('destino.'),
            ])
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($grupo) use ($busqueda): void {
                $grupo->whereHas('pieza', fn ($pieza) => $pieza->withTrashed()->where('nombre', 'like', "%{$busqueda}%"))
                    ->orWhere('motivo', 'like', "%{$busqueda}%");
            }))
            ->when($accion !== '', fn ($consulta) => $consulta->where('tipo_movimiento', $accion))
            ->when($desde !== null, fn ($consulta) => $consulta->whereDate('fecha', '>=', $desde))
            ->when($hasta !== null, fn ($consulta) => $consulta->whereDate('fecha', '<=', $hasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id_movimiento')
            ->paginate(15)
            ->withQueryString();

        return view('movimientos.index', [
            'movimientos' => $movimientos,
            'acciones' => Movimiento::ACCIONES,
            'filtros' => ['q' => $busqueda, 'accion' => $accion, 'desde' => $desde, 'hasta' => $hasta],
            'hayFiltros' => $busqueda !== '' || $accion !== '' || $desde !== null || $hasta !== null,
        ]);
    }

    /**
     * GET /movimientos/crear?pieza=ID&accion=venta
     * Sin pieza muestra el selector de pieza; con pieza, el formulario adaptado a la acción.
     */
    public function create(Request $request): View
    {
        $idPieza = $this->textoDeConsulta($request, 'pieza');

        if (! ctype_digit($idPieza)) {
            return view('movimientos.create', [
                'pieza' => null,
                'piezas' => Pieza::query()->orderBy('nombre')->get(['id_pieza', 'nombre', 'stock'])
                    ->mapWithKeys(fn (Pieza $pieza): array => [$pieza->id_pieza => "{$pieza->nombre} · {$pieza->stock} uds."])
                    ->all(),
            ]);
        }

        // La pieza debe ser de la sede del usuario: si no, 404 como si no existiera.
        $pieza = Pieza::query()->findOrFail((int) $idPieza);
        $accion = $this->textoDeConsulta($request, 'accion');

        return view('movimientos.create', [
            'pieza' => $pieza,
            'accion' => array_key_exists($accion, Movimiento::ACCIONES) ? $accion : Movimiento::ENTRADA,
            'acciones' => $this->accionesPermitidas($request),
            'origenes' => OpcionesInventario::origenes($pieza),
            'huecos' => OpcionesInventario::huecos(),
            'claveEnvio' => (string) Str::uuid(),
            'puedeRetirarFicha' => $request->user()->can('piezas.eliminar'),
        ]);
    }

    /**
     * POST /piezas/{pieza}/movimientos
     */
    public function store(MovimientoRequest $request, Pieza $pieza, InventarioPiezas $inventario): RedirectResponse
    {
        $accion = $request->validated('accion');

        $movimientos = $inventario->registrar(
            $pieza,
            $accion,
            $request->datosDelMovimiento(),
            $request->user(),
            $request->validated('clave_envio'),
        );

        $retirada = $accion === Movimiento::BAJA && $request->datosDelMovimiento()['retirar_ficha'];

        if ($retirada) {
            return redirect()
                ->route('movimientos.index')
                ->with('success', "La pieza «{$pieza->nombre}» se ha retirado y queda archivada con su historial.");
        }

        return redirect()
            ->route('piezas.show', $pieza)
            ->with('success', 'Movimiento «'.Movimiento::ACCIONES[$accion].'» registrado ('.$movimientos->sum('cantidad').' uds.). Stock actual: '.$pieza->fresh()->stock.'.');
    }

    /**
     * @return array<string, string>
     */
    private function accionesPermitidas(Request $request): array
    {
        return $request->user()->can('movimientos.crear') ? Movimiento::ACCIONES : [];
    }

    private function textoDeConsulta(Request $request, string $clave): string
    {
        $valor = $request->query($clave);

        return is_string($valor) ? trim($valor) : '';
    }

    private function fechaDeConsulta(Request $request, string $clave): ?string
    {
        $valor = $this->textoDeConsulta($request, $clave);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes) !== 1) {
            return null;
        }

        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) ? $valor : null;
    }
}
