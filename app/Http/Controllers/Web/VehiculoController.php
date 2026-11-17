<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GuardarVehiculoRequest;
use App\Models\Desguace;
use App\Models\UbicacionVehiculo;
use App\Models\Vehiculo;
use App\Support\JerarquiaUbicacion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class VehiculoController extends Controller
{
    /**
     * Listado con buscador, filtros y paginación.
     */
    public function index(Request $request): View
    {
        // Los filtros no válidos (fechas mal escritas, ids no numéricos…) se descartan sin error.
        $filtros = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'desguace' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ])->valid();

        $vehiculos = Vehiculo::query()
            ->with('desguace')
            ->when($filtros['q'] ?? null, function ($consulta, string $texto): void {
                $consulta->where(function ($busqueda) use ($texto): void {
                    $patron = '%'.trim($texto).'%';
                    $busqueda->where('matricula', 'like', $patron)
                        ->orWhere('marca', 'like', $patron)
                        ->orWhere('modelo', 'like', $patron)
                        ->orWhere('numero_bastidor', 'like', $patron);
                });
            })
            ->when($filtros['desguace'] ?? null, fn ($consulta, $idDesguace) => $consulta->where('id_desguace', $idDesguace))
            ->when($filtros['estado'] ?? null, fn ($consulta, string $estado) => $consulta->where('estado_tratamiento', $estado))
            ->when($filtros['desde'] ?? null, fn ($consulta, string $desde) => $consulta->whereDate('fecha_llegada', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($consulta, string $hasta) => $consulta->whereDate('fecha_llegada', '<=', $hasta))
            ->orderByDesc('fecha_llegada')
            ->orderByDesc('id_vehiculo')
            ->paginate(15)
            ->withQueryString();

        return view('vehiculos.index', [
            'vehiculos' => $vehiculos,
            'filtros' => $filtros,
            'desguaces' => $this->opcionesDesguace(),
            'estados' => $this->opcionesEstado(),
        ]);
    }

    public function create(): View
    {
        return view('vehiculos.create', [
            'vehiculo' => new Vehiculo,
            'desguaces' => $this->opcionesDesguace(),
            'estados' => $this->opcionesEstado(),
        ]);
    }

    public function store(GuardarVehiculoRequest $request): RedirectResponse
    {
        $vehiculo = new Vehiculo;
        $vehiculo->fill($request->validated())->save();

        return redirect()
            ->route('vehiculos.show', $vehiculo)
            ->with('success', 'Vehículo registrado correctamente.');
    }

    /**
     * Ficha del vehículo con su ubicación vigente y las categorías asociadas.
     */
    public function show(Vehiculo $vehiculo): View
    {
        $vehiculo->load([
            'desguace' => fn ($consulta) => $consulta->withTrashed(),
            'categorias',
        ]);

        $ubicacionActual = UbicacionVehiculo::query()
            ->with(JerarquiaUbicacion::relaciones())
            ->where('id_vehiculo', $vehiculo->getKey())
            ->whereNull('fecha_fin')
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_ubicacion')
            ->first();

        return view('vehiculos.show', [
            'vehiculo' => $vehiculo,
            'ubicacionActual' => $ubicacionActual,
        ]);
    }

    public function edit(Vehiculo $vehiculo): View
    {
        return view('vehiculos.edit', [
            'vehiculo' => $vehiculo,
            'desguaces' => $this->opcionesDesguace(),
            'estados' => $this->opcionesEstado(),
        ]);
    }

    public function update(GuardarVehiculoRequest $request, Vehiculo $vehiculo): RedirectResponse
    {
        $vehiculo->fill($request->validated())->save();

        return redirect()
            ->route('vehiculos.show', $vehiculo)
            ->with('success', 'Vehículo actualizado correctamente.');
    }

    /**
     * Borrado lógico, igual que en la API.
     */
    public function destroy(Vehiculo $vehiculo): RedirectResponse
    {
        $vehiculo->delete();

        return redirect()
            ->route('vehiculos.index')
            ->with('success', 'Vehículo eliminado correctamente.');
    }

    /**
     * @return Collection<int, string> nombre de cada desguace indexado por su id
     */
    private function opcionesDesguace()
    {
        return Desguace::query()->orderBy('nombre')->pluck('nombre', 'id_desguace');
    }

    /**
     * Estados de tratamiento que existen realmente en la base de datos.
     *
     * @return Collection<string, string>
     */
    private function opcionesEstado()
    {
        return Vehiculo::query()
            ->whereNotNull('estado_tratamiento')
            ->distinct()
            ->orderBy('estado_tratamiento')
            ->pluck('estado_tratamiento')
            ->mapWithKeys(fn (string $estado): array => [$estado => $estado]);
    }
}
