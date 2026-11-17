<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AsignarUbicacionRequest;
use App\Models\Fila;
use App\Models\Nivel;
use App\Models\Patio;
use App\Models\Posicion;
use App\Models\Sector;
use App\Models\UbicacionVehiculo;
use App\Models\Vehiculo;
use App\Services\AsignacionUbicacionVehiculo;
use App\Services\OcupacionAlmacen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * Pantalla 19: asignación (alta o traslado) de la ubicación de un vehículo.
 *
 * El selector en cascada patio → sector → fila → posición se resuelve con pasos por GET
 * (?patio=&sector=&fila=&posicion=), de modo que funciona sin JavaScript.
 */
class AsignacionUbicacionController extends Controller
{
    public function __construct(private readonly OcupacionAlmacen $ocupacion) {}

    public function create(Request $request, Vehiculo $vehiculo): View
    {
        $vehiculo->load('desguace');

        $ubicaciones = UbicacionVehiculo::query()
            ->with(OcupacionAlmacen::relacionesRutaFisica())
            ->where('id_vehiculo', $vehiculo->getKey())
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_ubicacion')
            ->get();

        return view('ubicaciones.asignar', [
            'vehiculo' => $vehiculo,
            'ubicacionVigente' => $ubicaciones->first(fn (UbicacionVehiculo $ubicacion): bool => $ubicacion->fecha_fin === null),
            'historialReciente' => $ubicaciones->take(5),
            'cascada' => $this->cascada($request, $vehiculo),
        ]);
    }

    public function store(AsignarUbicacionRequest $request, Vehiculo $vehiculo, AsignacionUbicacionVehiculo $asignacion): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $asignacion->asignar(
                $vehiculo,
                (int) $datos['id_nivel'],
                $datos['fecha_inicio'],
                $datos['fecha_fin'] ?? null,
            );
        } catch (ValidationException $excepcion) {
            // Los errores se muestran junto al formulario, en el mismo paso de la cascada.
            throw $excepcion->redirectTo(route('vehiculos.ubicaciones.asignar', ['vehiculo' => $vehiculo] + $request->pasosDeLaCascada()));
        }

        return redirect()
            ->to($this->destinoTrasGuardar($vehiculo))
            ->with('success', 'La ubicación del vehículo '.$vehiculo->matricula.' se ha guardado correctamente.');
    }

    /**
     * Tras guardar se vuelve al historial del vehículo o a su ficha si esas pantallas existen
     * (las implementa otra rama); si no, a esta misma pantalla.
     */
    private function destinoTrasGuardar(Vehiculo $vehiculo): string
    {
        foreach (['vehiculos.ubicaciones.index', 'vehiculos.show'] as $nombre) {
            if (Route::has($nombre)) {
                return route($nombre, ['vehiculo' => $vehiculo]);
            }
        }

        return route('vehiculos.ubicaciones.asignar', ['vehiculo' => $vehiculo]);
    }

    /**
     * Opciones de cada paso del selector. Un valor de la URL que no pertenece al paso anterior
     * se ignora, de modo que al cambiar un paso superior se reinician los inferiores.
     *
     * @return array{
     *     patios: Collection<int, Patio>, sectores: Collection<int, Sector>, filas: Collection<int, Fila>,
     *     posiciones: Collection<int, Posicion>, niveles: Collection<int, Nivel>,
     *     patio: ?Patio, sector: ?Sector, fila: ?Fila, posicion: ?Posicion,
     *     ocupadosPorPosicion: array<int, int>, ubicacionPorNivel: Collection<int, UbicacionVehiculo>
     * }
     */
    private function cascada(Request $request, Vehiculo $vehiculo): array
    {
        $patios = Patio::query()->where('id_desguace', $vehiculo->id_desguace)->orderBy('codigo')->get();
        $patio = $patios->firstWhere('id_patio', $this->idDeConsulta($request, 'patio'));

        $sectores = $patio ? Sector::query()->where('id_patio', $patio->id_patio)->orderBy('codigo')->get() : collect();
        $sector = $sectores->firstWhere('id_sector', $this->idDeConsulta($request, 'sector'));

        $filas = $sector ? Fila::query()->where('id_sector', $sector->id_sector)->orderBy('id_fila')->get() : collect();
        $fila = $filas->firstWhere('id_fila', $this->idDeConsulta($request, 'fila'));

        $posiciones = $fila ? Posicion::query()->where('id_fila', $fila->id_fila)->orderBy('id_posicion')->get() : collect();
        $posicion = $posiciones->firstWhere('id_posicion', $this->idDeConsulta($request, 'posicion'));

        $niveles = $posicion ? Nivel::query()->where('id_posicion', $posicion->id_posicion)->orderBy('id_nivel')->get() : collect();

        return [
            'patios' => $patios,
            'sectores' => $sectores,
            'filas' => $filas,
            'posiciones' => $posiciones,
            'niveles' => $niveles,
            'patio' => $patio,
            'sector' => $sector,
            'fila' => $fila,
            'posicion' => $posicion,
            'ocupadosPorPosicion' => $this->ocupacion->ocupadosPorPosicion($posiciones->pluck('id_posicion')->all()),
            'ubicacionPorNivel' => $this->ocupacion->ubicacionVigentePorNivel($niveles->pluck('id_nivel')->all()),
        ];
    }

    private function idDeConsulta(Request $request, string $clave): int
    {
        $valor = $request->query($clave);

        return is_scalar($valor) && ctype_digit((string) $valor) ? (int) $valor : 0;
    }
}
