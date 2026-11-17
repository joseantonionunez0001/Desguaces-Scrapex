<?php

namespace App\Http\Controllers\Web;

use App\Services\EstructuraAlmacen;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Pantalla 18: gestión del almacén exterior (Desguace → Patio → Sector → Fila → Posición → Nivel).
 *
 * La ocupación solo se calcula donde es fiable: en las posiciones (ubicaciones vigentes de sus
 * niveles frente a capacidad_maxima) y en los niveles (si tienen una ubicación vigente).
 */
class AlmacenExteriorController extends AlmacenController
{
    protected function zona(): string
    {
        return EstructuraAlmacen::EXTERIOR;
    }

    /**
     * @param  array<string, mixed>  $definicion
     * @return array<string, mixed>
     */
    protected function datosAdicionales(array $definicion, LengthAwarePaginator $elementos): array
    {
        $ids = $elementos->getCollection()->modelKeys();

        return match ($definicion['tipo']) {
            'posiciones' => ['ocupadosPorPosicion' => $this->ocupacion->ocupadosPorPosicion($ids)],
            'niveles' => ['ubicacionPorNivel' => $this->ocupacion->ubicacionVigentePorNivel($ids)],
            default => [],
        };
    }
}
