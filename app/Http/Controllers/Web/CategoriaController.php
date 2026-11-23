<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CategoriaRequest;
use App\Models\Categoria;
use App\Support\Acceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Categorías: alta y listado. Los tipos se gestionan en «Tipos de pieza» y las piezas en «Piezas».
 */
class CategoriaController extends Controller
{
    public function index(Request $request): View
    {
        $valor = $request->query('q');
        $busqueda = is_string($valor) ? trim($valor) : '';

        $categorias = Categoria::query()
            ->withCount(['tiposPieza', 'movimientos'])
            ->when($busqueda !== '', fn ($consulta) => $consulta->where('nombre', 'like', "%{$busqueda}%"))
            ->orderBy('nombre')
            ->orderBy('categoria_id')
            ->paginate(15)
            ->withQueryString();

        return view('categorias.index', [
            'categorias' => $categorias,
            'filtros' => ['q' => $busqueda],
            'hayFiltros' => $busqueda !== '',
        ]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        $categoria = new Categoria;
        $categoria->nombre = $request->validated('nombre');
        // La categoría pertenece al desguace del usuario: lo asigna el servidor.
        $categoria->id_desguace = Acceso::desguaceId($request->user());
        $categoria->save();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->nombre = $request->validated('nombre');
        $categoria->save();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        if ($categoria->tiposPieza()->exists() || $categoria->movimientos()->exists()) {
            return redirect()
                ->route('categorias.index')
                ->with('error', "No se puede eliminar la categoría «{$categoria->nombre}» porque tiene tipos de pieza o movimientos asociados.");
        }

        $categoria->delete();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}
