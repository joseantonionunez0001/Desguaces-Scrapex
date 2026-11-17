<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TipoPiezaRequest;
use App\Models\TipoPieza;
use App\Support\OpcionesCategorias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipoPiezaController extends Controller
{
    /**
     * Listado con buscador, filtro por categoría y datos de los modales de alta y edición.
     */
    public function index(Request $request): View
    {
        $busqueda = $this->textoDeConsulta($request, 'q');
        $categoriaId = ctype_digit($this->textoDeConsulta($request, 'categoria')) ? (int) $request->query('categoria') : null;

        $tiposPieza = TipoPieza::query()
            ->with(['categoriaRelacionada' => fn ($relacion) => $relacion->withTrashed()->with([
                'desguace' => fn ($desguace) => $desguace->withTrashed(),
            ])])
            ->withCount('piezas')
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($grupo) use ($busqueda): void {
                $grupo->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('fabricante', 'like', "%{$busqueda}%")
                    ->orWhere('categoria', 'like', "%{$busqueda}%");
            }))
            ->when($categoriaId !== null, fn ($consulta) => $consulta->where('id_categoria', $categoriaId))
            ->orderBy('nombre')
            ->orderBy('id_pieza')
            ->paginate(15)
            ->withQueryString();

        return view('tipos-pieza.index', [
            'tiposPieza' => $tiposPieza,
            'categorias' => OpcionesCategorias::paraSelect(),
            'categoriasTexto' => TipoPieza::query()->distinct()->orderBy('categoria')->pluck('categoria')->all(),
            'filtros' => ['q' => $busqueda, 'categoria' => $categoriaId],
            'hayFiltros' => $busqueda !== '' || $categoriaId !== null,
        ]);
    }

    public function store(TipoPiezaRequest $request): RedirectResponse
    {
        $tipoPieza = new TipoPieza;
        $tipoPieza->fill($request->validated());
        $tipoPieza->save();

        return redirect()
            ->route('tipos-pieza.index')
            ->with('success', 'Tipo de pieza creado correctamente.');
    }

    public function update(TipoPiezaRequest $request, TipoPieza $tipoPieza): RedirectResponse
    {
        $tipoPieza->fill($request->validated());
        $tipoPieza->save();

        return redirect()
            ->route('tipos-pieza.index')
            ->with('success', 'Tipo de pieza actualizado correctamente.');
    }

    /**
     * Borrado lógico. Se rechaza si el tipo tiene piezas activas (la clave foránea es restrictOnDelete).
     */
    public function destroy(TipoPieza $tipoPieza): RedirectResponse
    {
        if ($tipoPieza->piezas()->exists()) {
            return redirect()
                ->route('tipos-pieza.index')
                ->with('error', "No se puede eliminar el tipo de pieza «{$tipoPieza->nombre}» porque tiene piezas asociadas.");
        }

        $tipoPieza->delete();

        return redirect()
            ->route('tipos-pieza.index')
            ->with('success', 'Tipo de pieza eliminado correctamente.');
    }

    private function textoDeConsulta(Request $request, string $clave): string
    {
        $valor = $request->query($clave);

        return is_string($valor) ? trim($valor) : '';
    }
}
