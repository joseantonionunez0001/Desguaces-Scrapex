<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TipoPiezaRequest;
use App\Models\TipoPieza;
use App\Support\OpcionesInventario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tipos de pieza: listado, alta y edición, cada uno vinculado a su categoría.
 */
class TipoPiezaController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $categoria = is_scalar($request->query('categoria')) && ctype_digit((string) $request->query('categoria'))
            ? (int) $request->query('categoria')
            : null;

        $tipos = TipoPieza::query()
            ->with(['categoriaRelacionada' => fn ($consulta) => $consulta->withTrashed()])
            ->withCount('piezas')
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($grupo) use ($busqueda): void {
                $grupo->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('fabricante', 'like', "%{$busqueda}%");
            }))
            ->when($categoria !== null, fn ($consulta) => $consulta->where('id_categoria', $categoria))
            ->orderBy('nombre')
            ->orderBy('id_pieza')
            ->paginate(15)
            ->withQueryString();

        return view('tipos-pieza.index', [
            'tipos' => $tipos,
            'categorias' => OpcionesInventario::categorias(),
            'filtros' => ['q' => $busqueda, 'categoria' => $categoria],
            'hayFiltros' => $busqueda !== '' || $categoria !== null,
        ]);
    }

    public function store(TipoPiezaRequest $request): RedirectResponse
    {
        $tipoPieza = new TipoPieza;
        $tipoPieza->fill($request->safe()->only(['nombre', 'id_categoria', 'fabricante']));
        $tipoPieza->save();

        return redirect()
            ->route('tipos-pieza.index', ['categoria' => $tipoPieza->id_categoria])
            ->with('success', 'Tipo de pieza creado correctamente.');
    }

    public function update(TipoPiezaRequest $request, TipoPieza $tipoPieza): RedirectResponse
    {
        $tipoPieza->fill($request->safe()->only(['nombre', 'id_categoria', 'fabricante']));
        $tipoPieza->save();

        return redirect()
            ->route('tipos-pieza.index', ['categoria' => $tipoPieza->id_categoria])
            ->with('success', 'Tipo de pieza actualizado correctamente.');
    }

    public function destroy(TipoPieza $tipoPieza): RedirectResponse
    {
        if ($tipoPieza->piezas()->withTrashed()->exists()) {
            return redirect()
                ->route('tipos-pieza.index')
                ->with('error', "No se puede eliminar el tipo de pieza «{$tipoPieza->nombre}» porque tiene piezas asociadas.");
        }

        $tipoPieza->delete();

        return redirect()
            ->route('tipos-pieza.index')
            ->with('success', 'Tipo de pieza eliminado correctamente.');
    }
}
