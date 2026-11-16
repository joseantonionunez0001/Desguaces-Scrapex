<?php

namespace Database\Factories;

use App\Models\Desguace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Vehiculo>
 */
class VehiculoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $catalogo = [
            ['marca' => 'SEAT', 'modelo' => 'Ibiza'],
            ['marca' => 'Renault', 'modelo' => 'Clio'],
            ['marca' => 'Ford', 'modelo' => 'Focus'],
            ['marca' => 'Peugeot', 'modelo' => '308'],
            ['marca' => 'Volkswagen', 'modelo' => 'Golf'],
        ];

        $vehiculo = fake()->randomElement($catalogo);

        return [
            'id_desguace' => Desguace::factory(),
            'matricula' => fake()->unique()->regexify('[0-9]{4}[BCDFGHJKLMNPRSTVWXYZ]{3}'),
            'marca' => $vehiculo['marca'],
            'modelo' => $vehiculo['modelo'],
            'fecha_llegada' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'numero_bastidor' => fake()->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'estado_tratamiento' => fake()->randomElement(['Pendiente', 'En desmontaje', 'Descontaminado', 'Listo para venta']),
        ];
    }
}
