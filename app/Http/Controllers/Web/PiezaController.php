<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GuardarPiezaRequest;
use App\Models\Etiqueta;
use App\Models\ExistenciaPieza;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Services\InventarioPiezas;
use App\Support\OpcionesInventario;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Catálogo de piezas: listado con filtros combinables, alta con Entrada inicial, ficha con existencias
 * por hueco e historial, y edición de los datos descriptivos. El stock solo cambia con movimientos.
 */
class PiezaController extends Controller
{
    public function index(Request $request): View
    {
        // Los filtros no válidos se descartan sin error.
        $filtros = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'marca' => ['nullable', 'integer', 'min:1'],
            'modelo' => ['nullable', 'integer', 'min:1'],
            'categoria' => ['nullable', 'integer', 'min:1'],
            'tipo' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'string', 'max:50'],
            'disponibilidad' => ['nullable', 'in:'.implode(',', array_keys(Pieza::DISPONIBILIDADES))],
        ])->valid();

        $piezas = Pieza::query()
            ->with([
                'tipoPieza' => fn ($consulta) => $consulta->withTrashed(),
                'tipoPieza.categoriaRelacionada' => fn ($consulta) => $consulta->withTrashed(),
                'modeloVehiculo.marca',
            ])
            ->when($filtros['q'] ?? null, fn ($consulta, string $texto) => $consulta->where('nombre', 'like', '%'.trim($texto).'%'))
            ->when($filtros['marca'] ?? null, fn ($consulta, $idMarca) => $consulta->whereHas('modeloVehiculo', fn ($modelo) => $modelo->where('id_marca', $idMarca)))
            ->when($filtros['modelo'] ?? null, fn ($consulta, $idModelo) => $consulta->where('id_modelo_vehiculo', $idModelo))
            ->when($filtros['categoria'] ?? null, fn ($consulta, $idCategoria) => $consulta->whereHas('tipoPieza', fn ($tipo) => $tipo->where('id_categoria', $idCategoria)))
            ->when($filtros['tipo'] ?? null, fn ($consulta, $idTipo) => $consulta->where('id_tipo_pieza', $idTipo))
            ->when($filtros['estado'] ?? null, fn ($consulta, string $estado) => $consulta->where('estado', $estado))
            ->when($filtros['disponibilidad'] ?? null, fn ($consulta, string $disponibilidad) => $consulta->conDisponibilidad($disponibilidad))
            ->orderByDesc('id_pieza')
            ->paginate(15)
            ->withQueryString();

        return view('piezas.index', [
            'piezas' => $piezas,
            'filtros' => array_filter($filtros, fn ($valor) => filled($valor)),
            ...$this->opcionesDeFormulario(),
        ]);
    }

    public function create(): View
    {
        return view('piezas.create', [
            'pieza' => new Pieza,
            'claveEnvio' => (string) Str::uuid(),
            ...$this->opcionesDeFormulario(),
        ]);
    }

    /**
     * Alta de la pieza y su Entrada inicial en una sola transacción (InventarioPiezas::darDeAlta).
     * Un reenvío del mismo formulario no duplica la pieza: devuelve la ya registrada.
     */
    public function store(GuardarPiezaRequest $request, InventarioPiezas $inventario): RedirectResponse
    {
        $pieza = $inventario->darDeAlta(
            $request->datosDeLaPieza(),
            (int) $request->validated('cantidad'),
            (int) $request->validated('id_etiqueta'),
            $request->user(),
            $request->validated('clave_envio'),
        );

        return redirect()
            ->route('piezas.show', $pieza)
            ->with('success', 'Pieza registrada correctamente con su entrada inicial.');
    }

    public function show(Pieza $pieza): View
    {
        $pieza->load([
            'tipoPieza' => fn ($consulta) => $consulta->withTrashed(),
            'tipoPieza.categoriaRelacionada' => fn ($consulta) => $consulta->withTrashed(),
            'modeloVehiculo.marca',
            'usuario' => fn ($consulta) => $consulta->withTrashed(),
        ]);

        $existencias = ExistenciaPieza::query()
            ->where('id_pieza', $pieza->getKey())
            ->where('cantidad', '>', 0)
            ->with(['etiqueta' => fn ($consulta) => $consulta->withTrashed(), ...Etiqueta::relacionesDeRuta('etiqueta.')])
            // El stock pendiente de ubicar aparece el último.
            ->orderByRaw('id_etiqueta is null')
            ->orderBy('id_etiqueta')
            ->get();

        $historial = Movimiento::query()
            ->where('id_pieza', $pieza->getKey())
            ->with([
                'usuario' => fn ($consulta) => $consulta->withTrashed(),
                'origen' => fn ($consulta) => $consulta->withTrashed(),
                'destino' => fn ($consulta) => $consulta->withTrashed(),
                ...Etiqueta::relacionesDeRuta('origen.'),
                ...Etiqueta::relacionesDeRuta('destino.'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id_movimiento')
            ->paginate(15, ['*'], 'historial')
            ->withQueryString();

        return view('piezas.show', [
            'pieza' => $pieza,
            'existencias' => $existencias,
            'historial' => $historial,
        ]);
    }

    public function edit(Pieza $pieza): View
    {
        $pieza->load(['modeloVehiculo', 'tipoPieza']);

        return view('piezas.edit', [
            'pieza' => $pieza,
            ...$this->opcionesDeFormulario(),
        ]);
    }

    /**
     * Solo datos descriptivos: el stock no se edita aquí, se registra con movimientos.
     */
    public function update(GuardarPiezaRequest $request, Pieza $pieza): RedirectResponse
    {
        $pieza->fill($request->datosDeLaPieza())->save();

        return redirect()
            ->route('piezas.show', $pieza)
            ->with('success', 'Pieza actualizada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcionesDeFormulario(): array
    {
        $modelos = OpcionesInventario::modelos();
        $tipos = OpcionesInventario::tipos();

        return [
            'marcas' => OpcionesInventario::marcas(),
            'modelos' => $modelos['opciones'],
            'marcaDeCadaModelo' => $modelos['padres'],
            'categorias' => OpcionesInventario::categorias(),
            'tiposPieza' => $tipos['opciones'],
            'categoriaDeCadaTipo' => $tipos['padres'],
            'estados' => OpcionesInventario::estadosFisicos(),
            'huecos' => OpcionesInventario::huecos(),
            'disponibilidades' => Pieza::DISPONIBILIDADES,
        ];
    }
}
