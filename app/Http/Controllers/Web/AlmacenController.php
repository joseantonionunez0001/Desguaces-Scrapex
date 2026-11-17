<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GuardarElementoAlmacenRequest;
use App\Models\Desguace;
use App\Services\AlmacenJerarquia;
use App\Services\EstructuraAlmacen;
use App\Services\OcupacionAlmacen;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lógica común de las pantallas de gestión del almacén (interior y exterior).
 *
 * El nivel mostrado depende del camino de la URL (?desguace=&modulo=&pasillo=…). Las altas,
 * ediciones y bajas llegan por rutas propias de cada tipo (el tipo viaja como valor por defecto
 * de la ruta) y siempre redirigen al listado del padre del elemento.
 */
abstract class AlmacenController extends Controller
{
    public function __construct(
        protected AlmacenJerarquia $jerarquia,
        protected OcupacionAlmacen $ocupacion,
    ) {}

    /**
     * Zona gestionada: EstructuraAlmacen::INTERIOR o EstructuraAlmacen::EXTERIOR.
     */
    abstract protected function zona(): string;

    public function index(Request $request): View
    {
        $zona = $this->zona();
        $camino = $this->jerarquia->resolverCamino($zona, $request->query());
        $desguace = $camino['desguace'];
        $tipoListado = $camino['tipoListado'];

        $elementos = null;
        $datosAdicionales = [];
        if ($desguace !== null && $tipoListado !== null) {
            $elementos = $this->jerarquia->listar($tipoListado, (int) $camino['padreId']);
            $datosAdicionales = $this->datosAdicionales($tipoListado, $elementos);
        }

        return view("almacen.{$zona}.index", [
            'zona' => $zona,
            'desguaces' => Desguace::query()->orderBy('nombre')->get(),
            'desguace' => $desguace,
            'migas' => $this->migas($zona, $camino),
            'consultaActual' => $this->consultaDelCamino($camino),
            'tipoListado' => $tipoListado,
            'padreId' => $camino['padreId'],
            'elementos' => $elementos,
            'datosAdicionales' => $datosAdicionales,
        ]);
    }

    public function store(GuardarElementoAlmacenRequest $request): RedirectResponse
    {
        $definicion = $request->definicion();

        $elemento = new $definicion['modelo'];
        $elemento->fill($request->datosDelElemento());
        $elemento->save();

        return $this->volverAlListado($definicion, $elemento)
            ->with('success', 'Se ha creado el '.$definicion['singular'].' «'.EstructuraAlmacen::titulo($definicion['tipo'], $elemento).'».');
    }

    public function update(GuardarElementoAlmacenRequest $request, int $id): RedirectResponse
    {
        $definicion = $request->definicion();

        $elemento = $definicion['modelo']::query()->findOrFail($id);
        $elemento->fill($request->datosDelElemento());
        $elemento->save();

        return $this->volverAlListado($definicion, $elemento)
            ->with('success', 'Se ha actualizado el '.$definicion['singular'].' «'.EstructuraAlmacen::titulo($definicion['tipo'], $elemento).'».');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $definicion = EstructuraAlmacen::definicion((string) $request->route('tipo'));

        $elemento = $definicion['modelo']::query()->findOrFail($id);
        $motivo = $this->jerarquia->motivoBloqueoEliminacion($definicion, $elemento);

        if ($motivo !== null) {
            return $this->volverAlListado($definicion, $elemento)->with('error', $motivo);
        }

        $elemento->delete();

        return $this->volverAlListado($definicion, $elemento)
            ->with('success', 'Se ha eliminado el '.$definicion['singular'].' «'.EstructuraAlmacen::titulo($definicion['tipo'], $elemento).'».');
    }

    /**
     * Datos extra del nivel mostrado (por ejemplo, la ocupación en el almacén exterior).
     *
     * @param  array<string, mixed>  $definicion
     * @return array<string, mixed>
     */
    protected function datosAdicionales(array $definicion, LengthAwarePaginator $elementos): array
    {
        return [];
    }

    /**
     * Redirige al listado del padre del elemento (el camino se obtiene de la base de datos).
     *
     * @param  array<string, mixed>  $definicion
     */
    private function volverAlListado(array $definicion, object $elemento): RedirectResponse
    {
        $camino = $this->jerarquia->caminoDesdePadre($definicion['tipo'], (int) $elemento->{$definicion['columna_padre']});

        return redirect()->route("almacen.{$this->zona()}.index", $camino ?? []);
    }

    /**
     * Parámetros de consulta del camino elegido: ['desguace' => 1, 'modulo' => 4…].
     *
     * @param  array{desguace: ?Desguace, seleccion: list<array{definicion: array<string, mixed>, elemento: Model}>}  $camino
     * @return array<string, int>
     */
    private function consultaDelCamino(array $camino): array
    {
        if ($camino['desguace'] === null) {
            return [];
        }

        $consulta = [EstructuraAlmacen::CLAVE_DESGUACE => (int) $camino['desguace']->getKey()];
        foreach ($camino['seleccion'] as $paso) {
            $consulta[$paso['definicion']['clave']] = (int) $paso['elemento']->getKey();
        }

        return $consulta;
    }

    /**
     * Migas del camino elegido, cada una con el enlace que vuelve a ese nivel.
     *
     * @param  array{desguace: ?Desguace, seleccion: list<array{definicion: array<string, mixed>, elemento: Model}>}  $camino
     * @return list<array{etiqueta: string, texto: string, url: string}>
     */
    private function migas(string $zona, array $camino): array
    {
        if ($camino['desguace'] === null) {
            return [];
        }

        $ruta = "almacen.{$zona}.index";
        $consulta = [EstructuraAlmacen::CLAVE_DESGUACE => (int) $camino['desguace']->getKey()];
        $migas = [[
            'etiqueta' => 'Desguace',
            'texto' => (string) $camino['desguace']->nombre,
            'url' => route($ruta, $consulta),
        ]];

        foreach ($camino['seleccion'] as $paso) {
            $consulta[$paso['definicion']['clave']] = (int) $paso['elemento']->getKey();
            $migas[] = [
                'etiqueta' => ucfirst($paso['definicion']['singular']),
                'texto' => EstructuraAlmacen::titulo($paso['definicion']['tipo'], $paso['elemento']),
                'url' => route($ruta, $consulta),
            ];
        }

        return $migas;
    }
}
