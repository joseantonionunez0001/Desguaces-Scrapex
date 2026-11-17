<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CategoriaRequest;
use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Modulo;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    /**
     * Listado con buscador, filtro por desguace y datos de los modales de alta y edición.
     */
    public function index(Request $request): View
    {
        $busqueda = $this->textoDeConsulta($request, 'q');
        $desguaceId = ctype_digit($this->textoDeConsulta($request, 'desguace')) ? (int) $request->query('desguace') : null;

        $categorias = Categoria::query()
            ->with([
                'desguace' => fn ($relacion) => $relacion->withTrashed(),
                'modulo' => fn ($relacion) => $relacion->withTrashed(),
                'vehiculo' => fn ($relacion) => $relacion->withTrashed(),
            ])
            ->withCount(['tiposPieza', 'movimientos'])
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($grupo) use ($busqueda): void {
                $grupo->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhereHas('desguace', fn ($relacion) => $relacion->where('nombre', 'like', "%{$busqueda}%"))
                    ->orWhereHas('modulo', fn ($relacion) => $relacion->where('nombre_modulo', 'like', "%{$busqueda}%"))
                    ->orWhereHas('vehiculo', fn ($relacion) => $relacion->where('matricula', 'like', "%{$busqueda}%"));
            }))
            ->when($desguaceId !== null, fn ($consulta) => $consulta->where('id_desguace', $desguaceId))
            ->orderBy('nombre')
            ->orderBy('categoria_id')
            ->paginate(15)
            ->withQueryString();

        return view('categorias.index', [
            'categorias' => $categorias,
            'desguaces' => Desguace::query()->orderBy('nombre')->pluck('nombre', 'id_desguace')->all(),
            'modulos' => $this->opcionesDeModulos(),
            'vehiculos' => $this->opcionesDeVehiculos(),
            'filtros' => ['q' => $busqueda, 'desguace' => $desguaceId],
            'hayFiltros' => $busqueda !== '' || $desguaceId !== null,
        ]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        $categoria = new Categoria;
        $categoria->fill($request->validated() + ['id_vehiculo' => null]);
        $categoria->save();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->fill($request->validated() + ['id_vehiculo' => null]);
        $categoria->save();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Borrado lógico. Se rechaza si la categoría tiene tipos de pieza o movimientos activos,
     * igual que haría la clave foránea (restrictOnDelete) con un borrado físico: así no quedan huérfanos.
     */
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

    /**
     * Módulos con el formato [id_modulo => "Módulo · Desguace"] para elegir uno del desguace seleccionado.
     *
     * @return array<int, string>
     */
    private function opcionesDeModulos(): array
    {
        return Modulo::query()
            ->with('desguace')
            ->orderBy('nombre_modulo')
            ->get()
            ->mapWithKeys(fn (Modulo $modulo): array => [
                $modulo->id_modulo => $modulo->nombre_modulo.' · '.($modulo->desguace?->nombre ?? 'Sin desguace'),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function opcionesDeVehiculos(): array
    {
        return Vehiculo::query()
            ->orderBy('matricula')
            ->get(['id_vehiculo', 'matricula', 'marca', 'modelo'])
            ->mapWithKeys(fn (Vehiculo $vehiculo): array => [
                $vehiculo->id_vehiculo => "{$vehiculo->matricula} · {$vehiculo->marca} {$vehiculo->modelo}",
            ])
            ->all();
    }

    private function textoDeConsulta(Request $request, string $clave): string
    {
        $valor = $request->query($clave);

        return is_string($valor) ? trim($valor) : '';
    }
}
