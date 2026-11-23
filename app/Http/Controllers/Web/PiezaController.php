<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GuardarPiezaRequest;
use App\Models\Pieza;
use App\Models\TipoPieza;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class PiezaController extends Controller
{
    /**
     * Listado con buscador, filtros y paginación.
     */
    public function index(Request $request): View
    {
        // Los filtros no válidos se descartan sin error.
        $filtros = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'string', 'max:50'],
            'disponibilidad' => ['nullable', 'in:disponible,agotada,stock_bajo'],
        ])->valid();

        $piezas = Pieza::query()
            ->with(['tipoPieza' => fn ($consulta) => $consulta->withTrashed()])
            ->when($filtros['q'] ?? null, fn ($consulta, string $texto) => $consulta->where('nombre', 'like', '%'.trim($texto).'%'))
            ->when($filtros['tipo'] ?? null, fn ($consulta, $idTipo) => $consulta->where('id_tipo_pieza', $idTipo))
            ->when($filtros['estado'] ?? null, fn ($consulta, string $estado) => $consulta->where('estado', $estado))
            ->when($filtros['disponibilidad'] ?? null, function ($consulta, string $disponibilidad): void {
                match ($disponibilidad) {
                    'disponible' => $consulta->where('stock', '>', 0),
                    'agotada' => $consulta->where('stock', '<=', 0),
                    'stock_bajo' => $consulta->whereColumn('stock', '<=', 'stock_minimo'),
                };
            })
            ->orderByDesc('id_pieza')
            ->paginate(15)
            ->withQueryString();

        return view('piezas.index', [
            'piezas' => $piezas,
            'filtros' => $filtros,
            'tiposPieza' => $this->opcionesTipoPieza(),
            'estados' => $this->opcionesEstado(),
        ]);
    }

    public function create(): View
    {
        return view('piezas.create', [
            'pieza' => new Pieza,
            'tiposPieza' => $this->opcionesTipoPieza(),
            'estados' => $this->opcionesEstado(),
        ]);
    }

    public function store(GuardarPiezaRequest $request): RedirectResponse
    {
        $pieza = new Pieza;
        $pieza->fill($request->validated());
        // El registrador es siempre el usuario autenticado, nunca un dato del formulario.
        $pieza->id_usuario = $request->user()->getKey();
        $pieza->save();

        return redirect()
            ->route('piezas.show', $pieza)
            ->with('success', 'Pieza registrada correctamente.');
    }

    /**
     * Ficha de la pieza con su tipo, la categoría del tipo y el usuario que la registró.
     */
    public function show(Pieza $pieza): View
    {
        $pieza->load([
            'tipoPieza' => fn ($consulta) => $consulta->withTrashed(),
            'tipoPieza.categoriaRelacionada' => fn ($consulta) => $consulta->withTrashed(),
            'usuario' => fn ($consulta) => $consulta->withTrashed(),
        ]);

        return view('piezas.show', ['pieza' => $pieza]);
    }

    public function edit(Pieza $pieza): View
    {
        return view('piezas.edit', [
            'pieza' => $pieza,
            'tiposPieza' => $this->opcionesTipoPieza(),
            'estados' => $this->opcionesEstado(),
        ]);
    }

    /**
     * Actualiza la pieza conservando el usuario que la registró.
     */
    public function update(GuardarPiezaRequest $request, Pieza $pieza): RedirectResponse
    {
        $pieza->fill($request->validated())->save();

        return redirect()
            ->route('piezas.show', $pieza)
            ->with('success', 'Pieza actualizada correctamente.');
    }

    /**
     * Borrado lógico, igual que en la API.
     */
    public function destroy(Pieza $pieza): RedirectResponse
    {
        $pieza->delete();

        return redirect()
            ->route('piezas.index')
            ->with('success', 'Pieza eliminada correctamente.');
    }

    /**
     * @return Collection<int, string> "nombre (fabricante)" de cada tipo indexado por su id
     */
    private function opcionesTipoPieza()
    {
        return TipoPieza::query()
            ->orderBy('nombre')
            ->orderBy('fabricante')
            ->get(['id_pieza', 'nombre', 'fabricante'])
            ->mapWithKeys(fn (TipoPieza $tipo): array => [$tipo->id_pieza => $tipo->nombre.' ('.$tipo->fabricante.')']);
    }

    /**
     * Estados que existen realmente en la base de datos.
     *
     * @return Collection<string, string>
     */
    private function opcionesEstado()
    {
        return Pieza::query()
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado')
            ->mapWithKeys(fn (string $estado): array => [$estado => $estado]);
    }
}
