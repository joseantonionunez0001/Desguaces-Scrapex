<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etiqueta;
use App\Models\ModeloVehiculo;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Models\TipoPieza;
use App\Rules\ExisteEnMiSede;
use App\Services\InventarioPiezas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Piezas por API, con las mismas reglas de inventario que la web:
 * - el alta registra la Entrada inicial (cantidad y hueco) en la misma transacción;
 * - después el stock no se edita: cambia con POST /api/movimientos;
 * - el borrado retira la ficha completa con una baja (y la conserva archivada con su historial).
 * El usuario registrador es siempre el autenticado.
 */
class PiezaApiController extends Controller
{
    // GET /api/piezas
    public function index(): JsonResponse
    {
        $piezas = Pieza::with(['tipoPieza', 'modeloVehiculo.marca', 'existencias'])->get();

        return response()->json([
            'data' => $piezas,
            'success' => 1,
        ], 200);
    }

    // GET /api/piezas/1
    public function show(string $id): JsonResponse
    {
        $pieza = Pieza::with(['tipoPieza', 'modeloVehiculo.marca', 'existencias'])->findOrFail($id);

        return response()->json([
            'data' => $pieza,
            'success' => 1,
        ], 200);
    }

    /**
     * POST /api/piezas
     * Body: nombre, precio, estado, stock_minimo, id_tipo_pieza, id_modelo_vehiculo (opcional),
     *       cantidad (unidades recibidas), id_etiqueta (hueco), clave_envio (opcional, evita duplicados).
     */
    public function store(Request $request, InventarioPiezas $inventario): JsonResponse
    {
        $datos = $request->validate([
            ...$this->reglasDescriptivas(),
            'cantidad' => ['required', 'integer', 'min:1'],
            'id_etiqueta' => ['required', new ExisteEnMiSede(Etiqueta::class)],
            'clave_envio' => ['nullable', 'string', 'max:64'],
            'stock' => ['prohibited'],
            'id_usuario' => ['prohibited'],
        ]);

        $pieza = $inventario->darDeAlta(
            Arr::only($datos, ['nombre', 'precio', 'estado', 'stock_minimo', 'id_tipo_pieza', 'id_modelo_vehiculo']),
            (int) $datos['cantidad'],
            (int) $datos['id_etiqueta'],
            $request->user(),
            $datos['clave_envio'] ?? (string) Str::uuid(),
        );

        return response()->json([
            'data' => $pieza->load('existencias'),
            'success' => 1,
        ], 201);
    }

    /**
     * PUT /api/piezas/1 — solo datos descriptivos. El stock cambia con movimientos.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $pieza = Pieza::findOrFail($id);

        $datos = $request->validate([
            ...$this->reglasDescriptivas(),
            'stock' => ['prohibited'],
            'id_usuario' => ['prohibited'],
        ], [
            'stock.prohibited' => 'El stock solo cambia registrando movimientos.',
        ]);

        $pieza->fill(Arr::only($datos, ['nombre', 'precio', 'estado', 'stock_minimo', 'id_tipo_pieza', 'id_modelo_vehiculo']));
        $pieza->save();

        return response()->json([
            'data' => $pieza,
            'success' => 1,
        ], 200);
    }

    /**
     * DELETE /api/piezas/1  Body: {"motivo": "..."}
     * Registra la baja de todas sus unidades y archiva la ficha con su historial.
     */
    public function destroy(Request $request, string $id, InventarioPiezas $inventario): JsonResponse
    {
        $pieza = Pieza::findOrFail($id);
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        $inventario->registrar($pieza, Movimiento::BAJA, ['motivo' => $datos['motivo'], 'retirar_ficha' => true], $request->user(), (string) Str::uuid());

        return response()->json([
            'data' => 'Pieza retirada y archivada con su historial',
            'success' => 1,
        ], 200);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function reglasDescriptivas(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', 'string', 'max:50'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'id_tipo_pieza' => ['required', new ExisteEnMiSede(TipoPieza::class)],
            'id_modelo_vehiculo' => ['nullable', 'integer', Rule::exists((new ModeloVehiculo)->getTable(), 'id_modelo')],
        ];
    }
}
