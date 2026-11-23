<?php

namespace App\Services;

use App\Models\Nivel;
use App\Models\UbicacionVehiculo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Cálculo de la ocupación del almacén exterior.
 *
 * Una ubicación está VIGENTE cuando su fecha_fin es nula y no está eliminada lógicamente
 * (el modelo ya excluye las eliminadas). Con eso:
 *  - un nivel está ocupado si tiene al menos una ubicación vigente;
 *  - una posición tiene tantos "ocupados" como ubicaciones vigentes haya en sus niveles.
 *
 * No hay ocupación en filas, sectores ni patios: no se puede calcular de forma fiable
 * porque el esquema no guarda superficie ni capacidad en esos niveles.
 */
class OcupacionAlmacen
{
    /**
     * Consulta base de ubicaciones vigentes.
     *
     * @return Builder<UbicacionVehiculo>
     */
    public function ubicacionesVigentes(): Builder
    {
        return UbicacionVehiculo::query()->whereNull('ubicaciones_vehiculo.fecha_fin');
    }

    /**
     * Ubicaciones vigentes por posición: [id_posicion => nº de ubicaciones vigentes].
     *
     * @param  iterable<int>  $idsPosicion
     * @return array<int, int>
     */
    public function ocupadosPorPosicion(iterable $idsPosicion, ?int $excluirVehiculo = null): array
    {
        $ids = collect($idsPosicion)->all();

        if ($ids === []) {
            return [];
        }

        return $this->ubicacionesVigentes()
            ->join('niveles', 'niveles.id_nivel', '=', 'ubicaciones_vehiculo.id_nivel')
            ->whereIn('niveles.id_posicion', $ids)
            ->when($excluirVehiculo !== null, fn (Builder $consulta) => $consulta->where('ubicaciones_vehiculo.id_vehiculo', '!=', $excluirVehiculo))
            ->groupBy('niveles.id_posicion')
            ->selectRaw('niveles.id_posicion as id_posicion, count(*) as total')
            ->pluck('total', 'id_posicion')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Ubicación vigente (con su vehículo) de cada nivel indicado, indexada por id_nivel.
     *
     * @param  iterable<int>  $idsNivel
     * @return Collection<int, UbicacionVehiculo>
     */
    public function ubicacionVigentePorNivel(iterable $idsNivel): Collection
    {
        $ids = collect($idsNivel)->all();

        if ($ids === []) {
            return collect();
        }

        return $this->ubicacionesVigentes()
            ->with('vehiculo')
            ->whereIn('id_nivel', $ids)
            ->orderBy('id_ubicacion')
            ->get()
            ->unique('id_nivel')
            ->keyBy('id_nivel');
    }

    /**
     * Nº de ubicaciones vigentes en un nivel (se usa para impedir eliminar niveles en uso).
     */
    public function contarVigentesEnNivel(int $idNivel): int
    {
        return $this->ubicacionesVigentes()->where('id_nivel', $idNivel)->count();
    }

    /**
     * Relaciones para cargar el camino físico de una ubicación (nivel → patio) incluyendo
     * elementos eliminados lógicamente, de modo que el historial siga siendo legible.
     *
     * @return array<string, \Closure>
     */
    public static function relacionesRutaFisica(string $prefijo = 'nivel'): array
    {
        $conEliminados = fn ($consulta) => $consulta->withTrashed();

        return [
            $prefijo => $conEliminados,
            "{$prefijo}.posicion" => $conEliminados,
            "{$prefijo}.posicion.fila" => $conEliminados,
            "{$prefijo}.posicion.fila.sector" => $conEliminados,
            "{$prefijo}.posicion.fila.sector.patio" => $conEliminados,
        ];
    }

    /**
     * Texto "Patio · Sector · Fila · Posición · Nivel" de un nivel con su camino ya cargado.
     */
    public static function describirNivel(?Nivel $nivel): string
    {
        if ($nivel === null) {
            return 'Ubicación no disponible';
        }

        $posicion = $nivel->posicion;
        $fila = $posicion?->fila;
        $sector = $fila?->sector;
        $patio = $sector?->patio;

        return collect([
            $patio ? 'Patio '.$patio->codigo : null,
            $sector ? 'Sector '.$sector->codigo : null,
            $fila ? 'Fila '.$fila->numero_fila : null,
            $posicion ? 'Posición '.$posicion->numero_posicion : null,
            'Nivel '.$nivel->numero_nivel,
        ])->filter()->implode(' · ');
    }
}
