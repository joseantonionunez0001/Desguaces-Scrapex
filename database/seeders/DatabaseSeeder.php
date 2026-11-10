<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Desguace;
use App\Models\Estanteria;
use App\Models\Etiqueta;
use App\Models\Fila;
use App\Models\Modulo;
use App\Models\Movimiento;
use App\Models\Nivel;
use App\Models\Pasillo;
use App\Models\Patio;
use App\Models\Pieza;
use App\Models\Posicion;
use App\Models\Sector;
use App\Models\TipoPieza;
use App\Models\UbicacionVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * El orden sigue las dependencias de claves foráneas: primero los padres
     * y después los hijos, pasando los ids explícitamente.
     */
    public function run(): void
    {
        // 0. Permisos y roles de Spatie. Deben existir antes de asignarlos.
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // 1. Desguaces.
        $desguaces = Desguace::factory(2)->create();

        // 2. Usuarios, cada uno asignado a un desguace aleatorio.
        $usuarios = User::factory(3)
            ->state(fn () => ['id_desguace' => $desguaces->random()->id_desguace])
            ->create();

        // 3. Estructura de almacenaje por desguace: módulos > pasillos > estanterías > etiquetas.
        $modulosPorDesguace = [];

        foreach ($desguaces as $desguace) {
            $modulos = Modulo::factory(2)
                ->state(['id_desguace' => $desguace->id_desguace])
                ->create();

            $modulosPorDesguace[$desguace->id_desguace] = $modulos;

            foreach ($modulos as $modulo) {
                $pasillos = Pasillo::factory(2)
                    ->state(['id_modulo' => $modulo->id_modulo])
                    ->create();

                foreach ($pasillos as $pasillo) {
                    $estanterias = Estanteria::factory(2)
                        ->state(['id_pasillo' => $pasillo->id_pasillo])
                        ->create();

                    foreach ($estanterias as $estanteria) {
                        Etiqueta::factory(2)
                            ->state(['id_estanteria' => $estanteria->id_estanteria])
                            ->create();
                    }
                }
            }
        }

        // 4. Estructura de patio por desguace: patio > sectores > filas > posiciones > niveles.
        $niveles = collect();

        foreach ($desguaces as $desguace) {
            $patio = Patio::factory()
                ->state(['id_desguace' => $desguace->id_desguace])
                ->create();

            $sectores = Sector::factory(2)
                ->state(['id_patio' => $patio->id_patio])
                ->create();

            foreach ($sectores as $sector) {
                $filas = Fila::factory(2)
                    ->state(['id_sector' => $sector->id_sector])
                    ->create();

                foreach ($filas as $fila) {
                    $posiciones = Posicion::factory(2)
                        ->state(['id_fila' => $fila->id_fila])
                        ->create();

                    foreach ($posiciones as $posicion) {
                        $niveles = $niveles->merge(
                            Nivel::factory(2)
                                ->state(['id_posicion' => $posicion->id_posicion])
                                ->create()
                        );
                    }
                }
            }
        }

        // 5. Vehículos, cada uno en un desguace aleatorio.
        $vehiculos = Vehiculo::factory(8)
            ->state(fn () => ['id_desguace' => $desguaces->random()->id_desguace])
            ->create();

        // 6. Categorías por desguace, cada una ligada a un módulo de ese desguace.
        $categorias = collect();

        foreach ($desguaces as $desguace) {
            $modulosDelDesguace = $modulosPorDesguace[$desguace->id_desguace];

            $categorias = $categorias->merge(
                Categoria::factory(2)
                    ->state(fn () => [
                        'id_desguace' => $desguace->id_desguace,
                        'id_modulo' => $modulosDelDesguace->random()->id_modulo,
                        'id_vehiculo' => null,
                    ])
                    ->create()
            );
        }

        // 7 y 8 y 9. Tipos de pieza, piezas y movimientos por categoría.
        foreach ($categorias as $categoria) {
            $tiposPieza = TipoPieza::factory(2)
                ->state(['id_categoria' => $categoria->categoria_id])
                ->create();

            foreach ($tiposPieza as $tipoPieza) {
                Pieza::factory(3)
                    ->state(fn () => [
                        'id_usuario' => $usuarios->random()->id,
                        'id_tipo_pieza' => $tipoPieza->id_pieza,
                    ])
                    ->create();
            }

            Movimiento::factory(2)
                ->state(['id_categoria' => $categoria->categoria_id])
                ->create();
        }

        // 10. Historial de ubicación: una ubicación cerrada y otra abierta por vehículo.
        foreach ($vehiculos as $vehiculo) {
            $inicioAnterior = Carbon::instance(fake()->dateTimeBetween('-1 year', '-6 months'));
            $finAnterior = $inicioAnterior->copy()->addDays(fake()->numberBetween(1, 90));
            $inicioActual = $finAnterior->copy()->addDay();

            UbicacionVehiculo::factory()
                ->state([
                    'id_vehiculo' => $vehiculo->id_vehiculo,
                    'id_nivel' => $niveles->random()->id_nivel,
                    'fecha_inicio' => $inicioAnterior->toDateString(),
                    'fecha_fin' => $finAnterior->toDateString(),
                ])
                ->create();

            UbicacionVehiculo::factory()
                ->state([
                    'id_vehiculo' => $vehiculo->id_vehiculo,
                    'id_nivel' => $niveles->random()->id_nivel,
                    'fecha_inicio' => $inicioActual->toDateString(),
                    'fecha_fin' => null,
                ])
                ->create();
        }
    }
}
