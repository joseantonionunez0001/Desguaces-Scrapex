<?php

namespace App\Services;

use App\Models\Nivel;
use App\Models\Posicion;
use App\Models\UbicacionVehiculo;
use App\Models\Vehiculo;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asignación (alta o traslado) de la ubicación de un vehículo.
 *
 * REGLAS DE NEGOCIO (supuestos pendientes de confirmar por el equipo):
 *  a) Un vehículo tiene como máximo una ubicación vigente (fecha_fin nula).
 *  b) Las fechas de un mismo vehículo no se solapan (fechas inclusivas). Al trasladar, la ubicación
 *     vigente se cierra el día anterior a la nueva fecha_inicio, que debe ser posterior a la suya.
 *  c) fecha_fin >= fecha_inicio.
 *  d) El nivel destino pertenece a un patio del mismo desguace que el vehículo.
 *  e) No se supera la capacidad_maxima de la posición (ubicaciones vigentes de otros vehículos < capacidad).
 *  f) No se puede elegir el mismo nivel que ya es la ubicación vigente.
 *  g) Un nivel solo aloja un vehículo vigente a la vez.
 *
 * El historial se conserva: la ubicación anterior nunca se borra, solo se cierra.
 */
class AsignacionUbicacionVehiculo
{
    /**
     * @throws ValidationException cuando se incumple alguna regla de negocio.
     */
    public function asignar(Vehiculo $vehiculo, int $idNivel, string $fechaInicio, ?string $fechaFin): UbicacionVehiculo
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin !== null && $fechaFin !== '' ? Carbon::parse($fechaFin)->startOfDay() : null;

        return DB::transaction(function () use ($vehiculo, $idNivel, $inicio, $fin): UbicacionVehiculo {
            // Se bloquea el vehículo para serializar traslados simultáneos del mismo vehículo.
            $vehiculoBloqueado = Vehiculo::query()->lockForUpdate()->findOrFail($vehiculo->getKey());

            $nivel = Nivel::query()
                ->with(['posicion.fila.sector.patio'])
                ->find($idNivel);
            $posicionDestino = $nivel?->posicion?->fila?->sector?->patio !== null ? $nivel->posicion : null;

            if ($nivel === null || $posicionDestino === null) {
                throw ValidationException::withMessages([
                    'id_nivel' => 'El nivel elegido ya no existe o su posición, fila, sector o patio ha sido eliminado.',
                ]);
            }

            // Regla (d): mismo desguace.
            if ((int) $nivel->posicion->fila->sector->patio->id_desguace !== (int) $vehiculoBloqueado->id_desguace) {
                throw ValidationException::withMessages([
                    'id_nivel' => 'El nivel elegido pertenece a un patio de otro desguace distinto al del vehículo.',
                ]);
            }

            // Se bloquea la posición para que dos asignaciones simultáneas no superen su capacidad.
            $posicion = Posicion::query()->lockForUpdate()->findOrFail($posicionDestino->getKey());

            $ubicaciones = UbicacionVehiculo::query()
                ->where('id_vehiculo', $vehiculoBloqueado->getKey())
                ->lockForUpdate()
                ->get();
            $vigentes = $ubicaciones->filter(fn (UbicacionVehiculo $ubicacion): bool => $ubicacion->fecha_fin === null);
            $cerradas = $ubicaciones->reject(fn (UbicacionVehiculo $ubicacion): bool => $ubicacion->fecha_fin === null);

            // Regla (f): el mismo nivel que la ubicación vigente.
            if ($vigentes->contains(fn (UbicacionVehiculo $ubicacion): bool => (int) $ubicacion->id_nivel === (int) $nivel->getKey())) {
                throw ValidationException::withMessages([
                    'id_nivel' => 'El vehículo ya está ubicado en ese nivel. Elige un nivel distinto para trasladarlo.',
                ]);
            }

            // Reglas (b) y (c) respecto a la ubicación vigente.
            foreach ($vigentes as $vigente) {
                if ($inicio->lessThanOrEqualTo($vigente->fecha_inicio)) {
                    throw ValidationException::withMessages([
                        'fecha_inicio' => 'La fecha de inicio debe ser posterior al inicio de la ubicación vigente ('.$this->formatear($vigente->fecha_inicio).').',
                    ]);
                }
            }

            // Regla (b) respecto al resto del historial (ubicaciones ya cerradas).
            foreach ($cerradas as $cerrada) {
                $terminaAntes = $cerrada->fecha_fin->lessThan($inicio);
                $empiezaDespues = $fin !== null && $cerrada->fecha_inicio->greaterThan($fin);

                if (! $terminaAntes && ! $empiezaDespues) {
                    throw ValidationException::withMessages([
                        'fecha_inicio' => 'Las fechas se solapan con una ubicación anterior del vehículo (del '
                            .$this->formatear($cerrada->fecha_inicio).' al '.$this->formatear($cerrada->fecha_fin).').',
                    ]);
                }
            }

            $this->comprobarOcupacion($posicion, $nivel, $vehiculoBloqueado);

            // Cierre de la ubicación anterior (regla a/b): el día anterior al inicio de la nueva.
            foreach ($vigentes as $vigente) {
                $vigente->fecha_fin = $inicio->copy()->subDay()->toDateString();
                $vigente->save();
            }

            $nueva = new UbicacionVehiculo;
            $nueva->fill([
                'id_vehiculo' => $vehiculoBloqueado->getKey(),
                'id_nivel' => $nivel->getKey(),
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin?->toDateString(),
            ]);
            $nueva->save();

            return $nueva;
        });
    }

    /**
     * Reglas (e) y (g): capacidad de la posición y nivel libre.
     */
    private function comprobarOcupacion(Posicion $posicion, Nivel $nivel, Vehiculo $vehiculo): void
    {
        $ocupacion = app(OcupacionAlmacen::class);

        // Las ubicaciones vigentes del propio vehículo se cierran en este traslado, así que no cuentan.
        $ocupadosPorOtros = $ocupacion->ocupadosPorPosicion([$posicion->getKey()], (int) $vehiculo->getKey())[$posicion->getKey()] ?? 0;

        if ($ocupadosPorOtros >= (int) $posicion->capacidad_maxima) {
            throw ValidationException::withMessages([
                'id_nivel' => "La posición {$posicion->numero_posicion} está completa ({$ocupadosPorOtros} de {$posicion->capacidad_maxima} plazas ocupadas).",
            ]);
        }

        $ocupanteDelNivel = $ocupacion->ubicacionesVigentes()
            ->where('id_nivel', $nivel->getKey())
            ->where('id_vehiculo', '!=', $vehiculo->getKey())
            ->exists();

        if ($ocupanteDelNivel) {
            throw ValidationException::withMessages([
                'id_nivel' => "El nivel {$nivel->numero_nivel} ya está ocupado por otro vehículo.",
            ]);
        }
    }

    private function formatear(CarbonInterface $fecha): string
    {
        return $fecha->format('d/m/Y');
    }
}
