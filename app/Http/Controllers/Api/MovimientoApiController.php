<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etiqueta;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Rules\ExisteEnMiSede;
use App\Services\InventarioPiezas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Movimientos por API. Las mismas cinco acciones que la web, siempre sobre una pieza, registradas con
 * InventarioPiezas. El historial es de solo lectura: PUT y DELETE se rechazan.
 */
class MovimientoApiController extends Controller
{
    // GET /api/movimientos
    public function index(): JsonResponse
    {
        $movimientos = Movimiento::with(['categoria', 'pieza'])->orderByDesc('id_movimiento')->get();

        return response()->json([
            'data' => $movimientos,
            'success' => 1,
        ], 200);
    }

    // GET /api/movimientos/1
    public function show(string $id): JsonResponse
    {
        $movimiento = Movimiento::with(['categoria', 'pieza'])->findOrFail($id);

        return response()->json([
            'data' => $movimiento,
            'success' => 1,
        ], 200);
    }

    /**
     * POST /api/movimientos
     * Body: id_pieza, accion (entrada|compra|venta|baja|traslado), cantidad, origen (id de etiqueta o
     *       "pendiente"), destino (id de etiqueta), motivo (bajas), retirar_ficha, clave_envio (opcional).
     */
    public function store(Request $request, InventarioPiezas $inventario): JsonResponse
    {
        $datos = $request->validate([
            'id_pieza' => ['required', new ExisteEnMiSede(Pieza::class)],
            'accion' => ['required', 'string', Rule::in(array_keys(Movimiento::ACCIONES))],
            'cantidad' => ['nullable', 'integer', 'min:1'],
            'origen' => ['nullable', 'string', 'regex:/^(pendiente|\d+)$/'],
            'destino' => ['nullable', new ExisteEnMiSede(Etiqueta::class)],
            'motivo' => ['nullable', 'string', 'max:500'],
            'retirar_ficha' => ['nullable', 'boolean'],
            'clave_envio' => ['nullable', 'string', 'max:64'],
        ]);

        $retirarFicha = (bool) ($datos['retirar_ficha'] ?? false);

        if ($retirarFicha && ! $request->user()->can('piezas.eliminar')) {
            return response()->json(['data' => 'No tienes permiso para retirar la ficha de una pieza.', 'success' => 0], 403);
        }

        $origen = $datos['origen'] ?? null;

        $movimientos = $inventario->registrar(
            Pieza::findOrFail($datos['id_pieza']),
            $datos['accion'],
            [
                'cantidad' => isset($datos['cantidad']) ? (int) $datos['cantidad'] : null,
                'origen' => $origen === null || $origen === 'pendiente' ? null : (int) $origen,
                'destino' => isset($datos['destino']) ? (int) $datos['destino'] : null,
                'motivo' => $datos['motivo'] ?? null,
                'retirar_ficha' => $retirarFicha,
            ],
            $request->user(),
            $datos['clave_envio'] ?? (string) Str::uuid(),
        );

        return response()->json([
            'data' => $movimientos,
            'success' => 1,
        ], 201);
    }

    // PUT /api/movimientos/1
    public function update(): JsonResponse
    {
        return $this->historialDeSoloLectura();
    }

    // DELETE /api/movimientos/1
    public function destroy(): JsonResponse
    {
        return $this->historialDeSoloLectura();
    }

    private function historialDeSoloLectura(): JsonResponse
    {
        return response()->json([
            'data' => 'El historial de movimientos no se modifica ni se borra. Registra un movimiento nuevo.',
            'success' => 0,
        ], 405);
    }
}
