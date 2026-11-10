<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoApiController extends Controller
{
    // GET /api/vehiculos
    public function index()
    {
        $vehiculos = Vehiculo::with('piezas')->get();

        return response()->json([
            'data' => $vehiculos,
            'success' => 1
        ], 200);
    }

    // GET /api/vehiculos/1
    public function show(string $id)
    {
        $vehiculo = Vehiculo::with('piezas')->findOrFail($id);

        return response()->json([
            'data' => $vehiculo,
            'success' => 1
        ], 200);
    }

    // POST /api/vehiculos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'anio' => 'required|integer|between:1980,2030'
        ]);

        $vehiculo = new Vehiculo();
        $vehiculo->marca = $datos['marca'];
        $vehiculo->modelo = $datos['modelo'];
        $vehiculo->anio = $datos['anio'];
        $vehiculo->save();

        return response()->json([
            'data' => $vehiculo,
            'success' => 1
        ], 201);
    }

    // PUT /api/vehiculos/1
    public function update(Request $request, string $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        $datos = $request->validate([
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'anio' => 'required|integer|between:1980,2030'
        ]);

        $vehiculo->marca = $datos['marca'];
        $vehiculo->modelo = $datos['modelo'];
        $vehiculo->anio = $datos['anio'];
        $vehiculo->save();

        return response()->json([
            'data' => $vehiculo,
            'success' => 1
        ], 200);
    }

    // DELETE /api/vehiculos/1
    public function destroy(string $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->delete();

        return response()->json([
            'data' => 'Vehiculo eliminado',
            'success' => 1
        ], 200);
    }

    // POST /api/vehiculos/{id}/vincular/{modo}
    public function vincularPiezas(Request $request, string $id, string $modo)
    {
        if (!in_array($modo, ['attach', 'detach', 'sync'], true)) {
            return response()->json([
                'data' => 'Modo no valido',
                'success' => 0
            ], 422);
        }

        $datos = $request->validate([
            'piezas' => 'present|array',
            'piezas.*' => 'required|integer|distinct|exists:piezas,id',
            'observaciones' => 'nullable|string|max:255'
        ]);

        $vehiculo = Vehiculo::findOrFail($id);
        $ids = $datos['piezas'];
        $observaciones = $datos['observaciones'] ?? null;

        if ($modo === 'attach') {
            foreach ($ids as $idPieza) {
                $existe = $vehiculo->piezas()
                    ->where('piezas.id', $idPieza)
                    ->exists();

                if (!$existe) {
                    $vehiculo->piezas()->attach(
                        $idPieza,
                        ['observaciones' => $observaciones]
                    );
                }
            }
        } elseif ($modo === 'detach') {
            $vehiculo->piezas()->detach($ids);
        } else {
            $relaciones = [];
            foreach ($ids as $idPieza) {
                $relaciones[$idPieza] = [
                    'observaciones' => $observaciones
                ];
            }
            $vehiculo->piezas()->sync($relaciones);
        }

        return response()->json([
            'data' => $vehiculo->load('piezas'),
            'success' => 1
        ], 200);
    }
}