<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UbicacionVehiculo;
use App\Models\Vehiculo;
use App\Support\JerarquiaUbicacion;
use Illuminate\Contracts\View\View;

/**
 * Historial de ubicaciones de un vehículo (solo lectura).
 */
class VehiculoUbicacionController extends Controller
{
    public function index(Vehiculo $vehiculo): View
    {
        $vehiculo->load(['desguace' => fn ($consulta) => $consulta->withTrashed()]);

        $consultaBase = UbicacionVehiculo::query()
            ->with(JerarquiaUbicacion::relaciones())
            ->where('id_vehiculo', $vehiculo->getKey());

        $ubicacionActual = (clone $consultaBase)
            ->whereNull('fecha_fin')
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_ubicacion')
            ->first();

        $ubicaciones = $consultaBase
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_ubicacion')
            ->paginate(15)
            ->withQueryString();

        // Duración en días; si sigue vigente se cuenta hasta hoy.
        $hoy = now()->startOfDay();
        $ubicaciones->getCollection()->each(function (UbicacionVehiculo $ubicacion) use ($hoy): void {
            $ubicacion->setAttribute(
                'duracion_dias',
                (int) max(0, $ubicacion->fecha_inicio->startOfDay()->diffInDays($ubicacion->fecha_fin ?? $hoy)),
            );
        });

        return view('vehiculos.ubicaciones', [
            'vehiculo' => $vehiculo,
            'ubicacionActual' => $ubicacionActual,
            'ubicaciones' => $ubicaciones,
        ]);
    }
}
