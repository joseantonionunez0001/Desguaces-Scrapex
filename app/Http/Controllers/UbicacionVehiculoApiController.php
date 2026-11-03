<?php

namespace App\Http\Controllers;

use App\Models\UbicacionVehiculo;
use Illuminate\Http\Request;

class UbicacionVehiculoApiController extends Controller
{
    // GET /api/ubicaciones-vehiculo
    public function index()
    {
        $ubicaciones = UbicacionVehiculo::all();

        return response()->json([
            'data' => $ubicaciones,
            'success' => 1
        ], 200);
    }

    // GET /api/ubicaciones-vehiculo/1
    public function show(string $id)
    {
        $ubicacion = UbicacionVehiculo::findOrFail($id);

        return response()->json([
            'data' => $ubicacion,
            'success' => 1
        ], 200);
    }

    // POST /api/ubicaciones-vehiculo
    public function store(Request $request)
    {
        $datos = $request->validate([
            'vehiculo_id' => 'required|integer|min:1',
            'nivel_id' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        if ($this->hayConflictoActivo($datos)) {
            return response()->json([
                'data' => 'El vehiculo o el nivel ya tienen una ubicacion activa',
                'success' => 0
            ], 422);
        }

        $ubicacion = new UbicacionVehiculo();
        $ubicacion->vehiculo_id = $datos['vehiculo_id'];
        $ubicacion->nivel_id = $datos['nivel_id'];
        $ubicacion->fecha_inicio = $datos['fecha_inicio'];
        $ubicacion->fecha_fin = $datos['fecha_fin'] ?? null;
        $ubicacion->save();

        return response()->json([
            'data' => $ubicacion,
            'success' => 1
        ], 201);
    }

    // PUT/PATCH /api/ubicaciones-vehiculo/1
    public function update(Request $request, string $id)
    {
        $ubicacion = UbicacionVehiculo::findOrFail($id);

        $datos = $request->validate([
            'vehiculo_id' => 'sometimes|required|integer|min:1',
            'nivel_id' => 'sometimes|required|integer|min:1',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|nullable|date'
        ]);

        // Se comprueba el conjunto final de datos, no solo los campos enviados
        $final = array_merge($ubicacion->only(['vehiculo_id', 'nivel_id', 'fecha_fin']), $datos);

        if ($this->hayConflictoActivo($final, $ubicacion->id)) {
            return response()->json([
                'data' => 'El vehiculo o el nivel ya tienen una ubicacion activa',
                'success' => 0
            ], 422);
        }

        $ubicacion->fill($datos);
        $ubicacion->save();

        return response()->json([
            'data' => $ubicacion,
            'success' => 1
        ], 200);
    }

    // DELETE /api/ubicaciones-vehiculo/1
    public function destroy(string $id)
    {
        $ubicacion = UbicacionVehiculo::findOrFail($id);
        $ubicacion->delete();

        return response()->json([
            'data' => 'Ubicacion eliminada',
            'success' => 1
        ], 200);
    }

    // Una ubicación activa (sin fecha_fin) no puede repetir vehículo ni nivel
    // con otra ubicación activa. Las ubicaciones con fecha_fin son historial.
    private function hayConflictoActivo(array $datos, ?int $ignorarId = null): bool
    {
        if (!empty($datos['fecha_fin'])) {
            return false;
        }

        $consulta = UbicacionVehiculo::whereNull('fecha_fin')
            ->where(function ($q) use ($datos) {
                $q->where('vehiculo_id', $datos['vehiculo_id'])
                    ->orWhere('nivel_id', $datos['nivel_id']);
            });

        if ($ignorarId !== null) {
            $consulta->where('id', '!=', $ignorarId);
        }

        return $consulta->exists();
    }
}
