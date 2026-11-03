<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UbicacionVehiculoFactory extends Factory
{
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'vehiculo_id' => fake()->numberBetween(1, 100),
            'nivel_id' => fake()->numberBetween(1, 100),
            'fecha_inicio' => $inicio,
            // Algunas ubicaciones quedan activas (fecha_fin null)
            'fecha_fin' => fake()->optional(0.5)->dateTimeBetween($inicio, '+1 year')
        ];
    }
}
