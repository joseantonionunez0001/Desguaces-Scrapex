<?php

namespace Database\Factories;

use App\Models\Nivel;
use App\Models\UbicacionVehiculo;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UbicacionVehiculo>
 */
class UbicacionVehiculoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UbicacionVehiculo>
     */
    protected $model = UbicacionVehiculo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-1 year', 'now');

        // La fecha de fin es opcional y, si existe, nunca es anterior al inicio.
        $fin = fake()->optional()->dateTimeBetween($inicio, 'now');

        return [
            'id_vehiculo' => Vehiculo::factory(),
            'id_nivel' => Nivel::factory(),
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin?->format('Y-m-d'),
        ];
    }
}
