<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AsignarAdministradorRequest;
use App\Http\Requests\Web\DesguaceRequest;
use App\Models\Desguace;
use App\Models\Scopes\AlcanceDesguaceScope;
use App\Models\User;
use App\Services\AdministracionDesguaces;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesguaceController extends Controller
{
    /**
     * Listado con buscador. La plataforma ve todos los desguaces y su administrador;
     * el personal de una sede solo ve la suya (scope global de Desguace) con sus contadores.
     */
    public function index(Request $request): View
    {
        $valor = $request->query('q');
        $busqueda = is_string($valor) ? trim($valor) : '';
        $usuario = $request->user();

        // Los contadores son datos de inventario: la plataforma no los ve.
        $mostrarInventario = $usuario->can('vehiculos.ver');
        $mostrarAdministrador = $usuario->can('desguaces.asignar_administrador');

        $desguaces = Desguace::query()
            ->when($mostrarInventario, fn ($consulta) => $consulta->withCount(['vehiculos', 'modulos', 'patios']))
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

        $administradores = $mostrarAdministrador
            ? User::role(RoleSeeder::ADMIN_DESGUACE)
                ->whereIn('id_desguace', $desguaces->getCollection()->modelKeys())
                ->orderBy('name')
                ->get()
                ->groupBy('id_desguace')
            : collect();

        return view('desguaces.index', [
            'desguaces' => $desguaces,
            'filtros' => ['q' => $busqueda],
            'hayFiltros' => $busqueda !== '',
            'mostrarInventario' => $mostrarInventario,
            'mostrarAdministrador' => $mostrarAdministrador,
            'administradores' => $administradores,
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
     * Designa o sustituye al administrador de la sede (solo administrador de plataforma).
     * Si el correo no corresponde a ninguna cuenta, se da de alta una nueva.
     */
    public function asignarAdministrador(
        AsignarAdministradorRequest $request,
        Desguace $desguace,
        AdministracionDesguaces $administracion,
    ): RedirectResponse {
        $existente = $request->cuentaExistente();

        try {
            $administrador = $existente !== null
                ? $administracion->designarAdministrador($desguace, $existente)
                : $administracion->altaAdministrador($desguace, $request->only(['name', 'email', 'password']));
        } catch (DomainException $excepcion) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors(['email' => $excepcion->getMessage()]);
        }

        return redirect()
            ->route('desguaces.index')
            ->with('success', "{$administrador->name} es ahora el administrador de «{$desguace->nombre}».");
    }

    /**
     * Borrado lógico. Se rechaza si el desguace tiene módulos, patios, vehículos, categorías o usuarios
     * activos (todas esas claves foráneas son restrictOnDelete), para no dejar datos huérfanos.
     *
     * Los recuentos se hacen sin el alcance por sede: la plataforma no ve el inventario, pero
     * no debe poder borrar una sede que tenga datos.
     */
    public function destroy(Desguace $desguace): RedirectResponse
    {
        $sinAlcance = fn ($relacion) => $relacion->withoutGlobalScope(AlcanceDesguaceScope::class)->count();

        $dependientes = collect([
            'módulos' => $sinAlcance($desguace->modulos()),
            'patios' => $sinAlcance($desguace->patios()),
            'vehículos' => $sinAlcance($desguace->vehiculos()),
            'categorías' => $sinAlcance($desguace->categorias()),
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
