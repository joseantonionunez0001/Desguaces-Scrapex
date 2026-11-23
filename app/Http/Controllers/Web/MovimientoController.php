<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\MovimientoRequest;
use App\Models\Movimiento;
use App\Support\OpcionesCategorias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovimientoController extends Controller
{
    /**
     * Listado paginado con buscador y filtros por tipo, categoría y rango de fechas.
     */
    public function index(Request $request): View
    {
        $busqueda = $this->textoDeConsulta($request, 'q');
        $tipo = $this->textoDeConsulta($request, 'tipo');
        $categoriaId = ctype_digit($this->textoDeConsulta($request, 'categoria')) ? (int) $request->query('categoria') : null;
        $desde = $this->fechaDeConsulta($request, 'desde');
        $hasta = $this->fechaDeConsulta($request, 'hasta');

        $movimientos = Movimiento::query()
            ->with(['categoria' => fn ($consulta) => $consulta->withTrashed()->with([
                'desguace' => fn ($relacion) => $relacion->withTrashed(),
                'modulo' => fn ($relacion) => $relacion->withTrashed(),
            ])])
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($grupo) use ($busqueda): void {
                $grupo->where('tipo_movimiento', 'like', "%{$busqueda}%")
                    ->orWhereHas('categoria', fn ($categoria) => $categoria->where('nombre', 'like', "%{$busqueda}%"));
            }))
            ->when($tipo !== '', fn ($consulta) => $consulta->where('tipo_movimiento', $tipo))
            ->when($categoriaId !== null, fn ($consulta) => $consulta->where('id_categoria', $categoriaId))
            ->when($desde !== null, fn ($consulta) => $consulta->whereDate('fecha', '>=', $desde))
            ->when($hasta !== null, fn ($consulta) => $consulta->whereDate('fecha', '<=', $hasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id_movimiento')
            ->paginate(15)
            ->withQueryString();

        return view('movimientos.index', [
            'movimientos' => $movimientos,
            'tipos' => $this->tiposDeMovimiento(),
            'categorias' => OpcionesCategorias::paraSelect(),
            'filtros' => [
                'q' => $busqueda,
                'tipo' => $tipo,
                'categoria' => $categoriaId,
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'hayFiltros' => $busqueda !== '' || $tipo !== '' || $categoriaId !== null || $desde !== null || $hasta !== null,
        ]);
    }

    /**
     * Formulario de registro de un movimiento.
     */
    public function create(): View
    {
        return view('movimientos.create', [
            'tipos' => $this->tiposDeMovimiento(),
            'categorias' => OpcionesCategorias::paraSelect(),
        ]);
    }

    /**
     * Guarda el movimiento. No modifica existencias de piezas: el esquema no relaciona
     * movimientos con piezas.
     */
    public function store(MovimientoRequest $request): RedirectResponse
    {
        $movimiento = new Movimiento;
        $movimiento->fill($request->validated());
        $movimiento->save();

        return redirect()
            ->route('movimientos.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }

    /**
     * Borrado lógico del movimiento (igual que la API).
     */
    public function destroy(Movimiento $movimiento): RedirectResponse
    {
        $movimiento->delete();

        return redirect()
            ->route('movimientos.index')
            ->with('success', 'Movimiento eliminado correctamente.');
    }

    /**
     * Tipos de movimiento que ya existen en la base de datos (la API admite cualquier texto de hasta 50 caracteres).
     *
     * @return array<string, string>
     */
    private function tiposDeMovimiento(): array
    {
        return Movimiento::query()
            ->distinct()
            ->orderBy('tipo_movimiento')
            ->pluck('tipo_movimiento', 'tipo_movimiento')
            ->all();
    }

    private function textoDeConsulta(Request $request, string $clave): string
    {
        $valor = $request->query($clave);

        return is_string($valor) ? trim($valor) : '';
    }

    /**
     * Devuelve la fecha (AAAA-MM-DD) solo si es válida; un valor mal formado se ignora.
     */
    private function fechaDeConsulta(Request $request, string $clave): ?string
    {
        $valor = $this->textoDeConsulta($request, $clave);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes) !== 1) {
            return null;
        }

        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) ? $valor : null;
    }
}
