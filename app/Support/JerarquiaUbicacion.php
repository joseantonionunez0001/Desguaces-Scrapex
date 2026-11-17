<?php

namespace App\Support;

/**
 * Relaciones que forman la jerarquía física de una ubicación de vehículo:
 * patio › sector › fila › posición › nivel.
 *
 * Se cargan con withTrashed() para que el historial siga mostrándose aunque
 * alguno de los elementos se haya eliminado lógicamente.
 */
class JerarquiaUbicacion
{
    /**
     * Relaciones a cargar (eager loading) desde UbicacionVehiculo.
     *
     * @return array<string, \Closure>
     */
    public static function relaciones(): array
    {
        $conBorrados = fn ($relacion) => $relacion->withTrashed();

        return [
            'nivel' => $conBorrados,
            'nivel.posicion' => $conBorrados,
            'nivel.posicion.fila' => $conBorrados,
            'nivel.posicion.fila.sector' => $conBorrados,
            'nivel.posicion.fila.sector.patio' => $conBorrados,
        ];
    }
}
