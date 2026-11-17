<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DesguaceRequest;
use App\Models\Desguace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesguaceController extends Controller
{
    /**
     * Listado con buscador, contadores y accesos a vehículos y almacenes de cada desguace.
     */
    public function index(Request $request): View
    {
        $valor = $request->query('q');
        $busqueda = is_string($valor) ? trim($valor) : '';

        $desguaces = Desguace::query()
            ->withCount(['vehiculos', 'modulos', 'patios'])
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($grupo) use ($busqueda): void {
                $grupo->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('direccion', 'like', "%{$busqueda}%")
                    ->orWhere('numero_telefono', 'like', "%{$busqueda}%")
                    ->orWhere('ubicación', 'like', "%{$busqueda}%");
            }))
            ->orderBy('nombre')
            ->orderBy('id_desguace')
            ->paginate(15)
            ->withQueryString();

        return view('desguaces.index', [
            'desguaces' => $desguaces,
            'filtros' => ['q' => $busqueda],
            'hayFiltros' => $busqueda !== '',
        ]);
    }

    public function store(DesguaceRequest $request): RedirectResponse
    {
        $desguace = new Desguace;
        $desguace->fill($request->validated());
        $desguace->save();

        return redirect()
            ->route('desguaces.index')
            ->with('success', 'Desguace creado correctamente.');
    }

    public function update(DesguaceRequest $request, Desguace $desguace): RedirectResponse
    {
        $desguace->fill($request->validated());
        $desguace->save();

        return redirect()
            ->route('desguaces.index')
            ->with('success', 'Desguace actualizado correctamente.');
    }

    /**
     * Borrado lógico. Se rechaza si el desguace tiene módulos, patios, vehículos, categorías o usuarios
     * activos (todas esas claves foráneas son restrictOnDelete), para no dejar datos huérfanos.
     */
    public function destroy(Desguace $desguace): RedirectResponse
    {
        $dependientes = collect([
            'módulos' => $desguace->modulos()->count(),
            'patios' => $desguace->patios()->count(),
            'vehículos' => $desguace->vehiculos()->count(),
            'categorías' => $desguace->categorias()->count(),
            'usuarios' => $desguace->usuarios()->count(),
        ])->filter();

        if ($dependientes->isNotEmpty()) {
            $detalle = $dependientes->map(fn (int $total, string $nombre): string => "{$total} {$nombre}")->implode(', ');

            return redirect()
                ->route('desguaces.index')
                ->with('error', "No se puede eliminar el desguace «{$desguace->nombre}» porque tiene datos asociados ({$detalle}).");
        }

        $desguace->delete();

        return redirect()
            ->route('desguaces.index')
            ->with('success', 'Desguace eliminado correctamente.');
    }
}
