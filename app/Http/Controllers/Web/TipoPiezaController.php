<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TipoPiezaRequest;
use App\Models\TipoPieza;
use Illuminate\Http\RedirectResponse;

/**
 * Alta, edición y baja de tipos de pieza. No tiene pantalla propia:
 * los tipos se gestionan desde la vista de categorías (modales dentro de cada categoría).
 */
class TipoPiezaController extends Controller
{
    public function store(TipoPiezaRequest $request): RedirectResponse
    {
        $tipoPieza = new TipoPieza;
        $tipoPieza->fill($request->validated());
        $tipoPieza->save();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Tipo de pieza creado correctamente.');
    }

    public function update(TipoPiezaRequest $request, TipoPieza $tipoPieza): RedirectResponse
    {
        $tipoPieza->fill($request->validated());
        $tipoPieza->save();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Tipo de pieza actualizado correctamente.');
    }

    /**
     * Borrado lógico. Se rechaza si el tipo tiene piezas activas (la clave foránea es restrictOnDelete).
     */
    public function destroy(TipoPieza $tipoPieza): RedirectResponse
    {
        if ($tipoPieza->piezas()->exists()) {
            return redirect()
                ->route('categorias.index')
                ->with('error', "No se puede eliminar el tipo de pieza «{$tipoPieza->nombre}» porque tiene piezas asociadas.");
        }

        $tipoPieza->delete();

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Tipo de pieza eliminado correctamente.');
    }
}
