<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UbicacionVehiculo;
use Illuminate\Http\Request;

class UbicacionVehiculoApiController extends Controller
{
    // GET /api/ubicaciones-vehiculo
    public function index()
    {
        $ubicaciones = UbicacionVehiculo::with(['vehiculo', 'nivel'])->get();

        return response()->json([
            'data' => $ubicaciones,
            'success' => 1
        ], 200);
    }

    // GET /api/ubicaciones-vehiculo/1
    public function show(string $id)
    {
        $ubicacion = UbicacionVehiculo::with(['vehiculo', 'nivel'])->findOrFail($id);

        return response()->json([
            'data' => $ubicacion,
            'success' => 1
        ], 200);
    }

    // POST /api/ubicaciones-vehiculo
    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'id_nivel' => 'required|exists:niveles,id_nivel',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $ubicacion = new UbicacionVehiculo();
        $ubicacion->id_vehiculo = $datos['id_vehiculo'];
        $ubicacion->id_nivel = $datos['id_nivel'];
        $ubicacion->fecha_inicio = $datos['fecha_inicio'];
        $ubicacion->fecha_fin = $datos['fecha_fin'] ?? null;
        $ubicacion->save();

        return response()->json([
            'data' => $ubicacion,
            'success' => 1
        ], 201);
    }

    // PUT /api/ubicaciones-vehiculo/1
    public function update(Request $request, string $id)
    {
        $ubicacion = UbicacionVehiculo::findOrFail($id);

        $datos = $request->validate([
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'id_nivel' => 'required|exists:niveles,id_nivel',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $ubicacion->id_vehiculo = $datos['id_vehiculo'];
        $ubicacion->id_nivel = $datos['id_nivel'];
        $ubicacion->fecha_inicio = $datos['fecha_inicio'];
        $ubicacion->fecha_fin = $datos['fecha_fin'] ?? null;
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
            'data' => 'Ubicacion de vehiculo eliminada',
            'success' => 1
        ], 200);
    }
}
